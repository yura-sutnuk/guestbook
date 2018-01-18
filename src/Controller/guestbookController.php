<?php

namespace App\Controller;

use App\Entity\GuestBookTable;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class guestbookController extends Controller
{
    private $maxPostsOnPage = 25;
    private $MIMETypeImg = ['png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',];
    private $MIMETypeText = ['txt' => 'text/plain'];

    /**
     * @Route ("guestbook/{currentPage}", name="guestbook", requirements={"currentPage" = "\d+"})
     */
    public function actionGuestBook($currentPage = 1,SessionInterface $session, Request $request)
    {
        $session->set('currentPage', $currentPage);
        $count = $this->getCountRows();
        $pages = $this->getPages($count,$currentPage);
        $posts = $this->getPostsOnPage( $session);
        foreach ($posts as &$post)
        {
            $post['ip'] = long2ip($post['ip']);

        }

        $userPost = new GuestBookTable();
        $form = $this->createFormBuilder($userPost)
            ->add('name',TextType::class,array(
                'label' => 'Имя пользователя',
                'attr' => array(
                    'class' => 'field',
                )
            ))
            ->add('email',TextType::class, array(
                'label'=> 'E-mail',
                'attr' => array(
                    'class' => 'field',
                )
            ))
            ->add('url', TextType::class,array(
                'label' => 'Homepage',
                'required' => false,
                'attr' => array(
                    'placeholder' => 'https://yandex.ua/',
                    'class' => 'field',
                )
            ))
            ->add('text', TextareaType::class,array(
                'attr' => array(
                    'cols' => 30,
                    'rows' => 4,
                    'class' => 'field',
                ),
            ))
            ->add('attachment',FileType::class,array(
                'label' => 'выберите файл ',
                'mapped' => false,
                'attr' => array(
                    'accept' => 'image/*, text/plain',
                    'onChange' => 'checkFileType(this)',
                    'class' => 'field',

                )
            ))
            ->add('captcha', TextType::class, array(
                'label' => '',
                'mapped' => false,
                'attr' => array(
                    'class' => 'field',
                )
            ))
            ->add('save',ButtonType::class, array(
                'label' => 'Отправить',
                'attr' => array(
                    'onClick' => 'checkData(form)'

                ),
            ))
            ->add('preview', ButtonType::class, array(
                'label' => 'Предпросмотр',
                'attr' => array(
                    'onClick' => 'preview(form)',
                )
            ))
            ->getForm();

        return $this->render('views/guestbook.html.twig', array(
            'posts' => $posts,
            'pages' => $pages,
            'form' => $form->createView(),

        ));
    }

    private function getPostsOnPage( SessionInterface $session)
    {
        $currentPage = $session->get('currentPage', 1);
        $sortedField = $session->get('sortedField','name');
        $sortedMode = $session->get('sortedMode', 'DESC');
        $start = ($currentPage-1) *$this->maxPostsOnPage;
        $end = $this->maxPostsOnPage;
        $sql = "SELECT * FROM guest_book_table ORDER BY ".$sortedField." ".$sortedMode." LIMIT ".$start.",".$end;

        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function getCountRows()
    {
        $sql = "SELECT COUNT(*) as count FROM guest_book_table";
        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll()[0]['count'];
    }

    private function getPages($count,$currentPage)
    {

        $pageCount = (int)ceil($count/$this->maxPostsOnPage);
        $start = $currentPage-2 > 0? $currentPage-2 : 1;
        $end = $currentPage+2 < $pageCount? $currentPage+2 : $pageCount;
        $htmlPage = '';
        if ($currentPage-2>1)
        {
            $htmlPage = "<a class='page' href ='/guestbook/1' >1</a> <span> ... </span>";
        }
        foreach (range($start,$end) as $page)
        {
            if($currentPage == $page )
            {
                $htmlPage .=  "<a class='page' id='activePage' href='/guestbook/".$page."'>".$page."</a>";

            }
            else
            {
                $htmlPage .= "<a class='page' href='/guestbook/".$page."'>".$page."</a>";
            }
        }

        if($currentPage+2 < $pageCount)
        {
            $htmlPage .= "<span>...</span><a class='page' href='/guestbook/".$pageCount." '>".$pageCount."</a>";
        }

        return $htmlPage;
    }
    /**
     * @Route ("addFormData")
     */
    public function addFormData (Request $request, SessionInterface $session)
    {
        $form = $request->request->get('form');
        $captcha = $request->request->get('captcha');

        if(md5($captcha) == $session->get('captcha',false)) {
            $userEntity = new GuestBookTable();
            $userEntity->setName($form['name']);
            $userEntity->setEmail($form['email']);
            $userEntity->setUrl($form['url']);
            $userEntity->setText(strip_tags($form['text'], '<b><strong><a><i><u><del><s><code>'));

            $fileName = '';

            if (isset($_FILES['attachment'])) {
                $MIMEType = mime_content_type($_FILES['attachment']['tmp_name']);
                if(in_array($MIMEType,$this->MIMETypeText) && $_FILES['attachment']['size'] < 100 *1024)
                {
                    $fileName = $this->moveUploadFile();
                }
                else if (in_array($MIMEType,$this->MIMETypeImg))
                {
                    $fileName = $this->resizeAndUploadFile($_FILES['attachment']);
                }
            }

            $userEntity->setAttachment($fileName);
            $ip = $request->getClientIp();
            $userEntity->setDate(DateTime::createFromFormat('d.m.Y', Date('d.m.Y')));
            $userEntity->setIp(ip2long($ip));
            $userEntity->setUserAgent($this->getUserBrowser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($userEntity);
            $em->flush();

            return new Response('1');//true
        }
        return new Response('0');//false
    }
    public function moveUploadFile()
    {
        $fileName = $_FILES['attachment']['name'];
        $fileDir = $_SERVER['DOCUMENT_ROOT'].'/files/'.$fileName;
        move_uploaded_file($_FILES['attachment']['tmp_name'],$fileDir );
        return $fileName;
    }


    private function resizeAndUploadFile($file)
    {   $dir = $_SERVER['DOCUMENT_ROOT']."/files/";
        $fileName = $file['name'];//$file->getClientOriginalName();
        $output = $dir . $fileName;
        $extension = explode('.', $fileName)[1];
        $maxW = 320;
        $maxH = 240;

        if ($extension == 'jpg')
        {
            $src = imagecreatefromjpeg($file['tmp_name']);
        }
        else if ($extension == 'gif')
        {
            $src = imagecreatefromgif($file['tmp_name']);
        }
        else if($extension == 'png')
        {
            $src = imagecreatefrompng($file['tmp_name']);
        }
        $wSrc = $wDest = imagesx($src);
        $hSrc = $hDest = imagesy($src);

        if($wDest > $maxW)
        {
            $ratio = $maxW/$wSrc;
            $wDest = $wSrc * $ratio;
            $hDest = $hSrc * $ratio;
        }
        if($hDest > $maxH)
        {
            $ratio = $maxH/$hSrc;
            $hDest = $hSrc * $ratio;
            $wDest = $wSrc * $ratio;
        }


        $dest = imagecreatetruecolor($wDest,$hDest);

        imagecopyresized($dest, $src, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
        if ($extension == 'jpg')
        {
            imagejpeg($dest, $output);
        }
        else if ($extension == 'gif')
        {
            imagegif ($dest,$output);
        }
        else if($extension == 'png')
        {
            imagepng($dest,$output);
        }
        imagedestroy($dest);
        imagedestroy($src);
        return $fileName;
    }


    private function getUserBrowser()
    {
        $user_agent = $_SERVER["HTTP_USER_AGENT"];
        if (strpos($user_agent, "Firefox") !== false) $browser = "Firefox";
        elseif (strpos($user_agent, "Opera") !== false) $browser = "Opera";
        elseif (strpos($user_agent, "Chrome") !== false) $browser = "Chrome";
        elseif (strpos($user_agent, "MSIE") !== false) $browser = "Internet Explorer";
        elseif (strpos($user_agent, "Safari") !== false) $browser = "Safari";
        else $browser = "Неизвестный";

        return $browser;
    }
    /**
     * @Route ("OrderName/{sort}", name="orderByName", requirements={"sort" = "DESC|ASC"})
     */
    public function orderByName($sort, SessionInterface $session)
    {
       // $sql = "SELECT * FROM guest_book_table ORDER BY `name` ".$sort." LIMIT 0,25";
        $session->set('sortedField','name');
        $session->set('sortedMode',$sort);
        $posts = $this->getPostsOnPage($session);
        $response = $this->getValidJsonData($posts);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function getValidJsonData($rows)
    {
        foreach ($rows as &$row)
        {
            $row['ip'] = long2ip($row['ip']);
        }
        return new Response(json_encode($rows));
    }
    /**
     * @Route ("OrderEmail/{sort}", name="orderByEmail", requirements={"sort" = "DESC|ASC"})
     */
    public function orderByEmail($sort, SessionInterface $session)
    {
        $session->set('sortedField','email');
        $session->set('sortedMode',$sort);
        $posts = $this->getPostsOnPage($session);
        $response = $this->getValidJsonData($posts);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route ("OrderDate/{sort}", name="orderByDate", requirements={"sort" = "DESC|ASC"})
     */
    public function orderByDate($sort, SessionInterface $session)
    {

        $session->set('sortedField','date');
        $session->set('sortedMode',$sort);
        $posts = $this->getPostsOnPage($session);
        $response = $this->getValidJsonData($posts);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route ("get/files/{fileName}", name="getFile")
     */
    public function getFileContent($fileName)
    {
       $fileDir = $_SERVER['DOCUMENT_ROOT'].'/files/'.$fileName;
        $data["error"] = "false";
        if(file_exists($fileDir))
        {
            $extension = explode('.',$fileName)[1];
            if($extension == 'txt')
            {
                $data["isText"] = "true";
                $data["fileContent"] = str_replace('\'','"',file_get_contents($fileDir));
                $data["fileContent"] = mb_convert_encoding($data["fileContent"],"UTF-8", "Windows-1252");
                $data["fileContent"] = htmlentities($data["fileContent"]);
                $data["fileContent"] = nl2br($data["fileContent"]);
            }
            else
            {
                $data["isText"] = "false";
                $data["fileContent"] = $fileName;
            }
        }
        else
        {
            $data["error"] = "true";
        }

        $response = new Response(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route ("getIpDateBrowser")
     */
    public function sendIpDateBrowser(Request $request)
    {
        $data['browser'] = $this->getUserBrowser();
        $data['ip'] = $request->getClientIp();
        $data['date'] = Date('Y.m.d H:i:s');
        $response = new Response(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


}