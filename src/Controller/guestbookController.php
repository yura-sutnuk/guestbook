<?php

namespace App\Controller;

use App\Entity\GuestBookTable;
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

    /**
     * @Route ("guestbook/{currentPage}", name="guestbook", requirements={"currentPage" = "\d+"})
     */
    public function actionGuestBook($currentPage = 1,SessionInterface $session)
    {
        $sortedField = $session->get('sortedField','name');
        $sortedMode = $session->get('sortedMode', 'DESC');
        $count = $this->getCountRows();
        $pages = $this->getPages($count,$currentPage);
        $posts = $this->getPostsOnPage($currentPage,['field' => $sortedField,'mode' => $sortedMode]);
        foreach ($posts as &$post)
        {
            $post['ip'] = long2ip($post['ip']);

        }
        return $this->render('views/guestbook.html.twig', array(
            'posts' => $posts,
            'pages' => $pages,
        ));
    }

    private function getPostsOnPage($currentPage, $sort)
    {
        $start = ($currentPage-1) *$this->maxPostsOnPage;
        $end = $this->maxPostsOnPage;
        $sql = "SELECT * FROM guest_book_table ORDER BY ".$sort['field']." ".$sort['mode']." LIMIT ".$start.",".$end;

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
     * @Route ("callback")
     */
    public function callbackForm(Request $request, SessionInterface $session)
    {
        $error = null;
        $userPost = new GuestBookTable();
        $form = $this->createFormBuilder($userPost)
            ->add('name',TextType::class,array(
                'label' => 'Имя пользователя',
            ))
            ->add('email',TextType::class, array(
                'label'=> 'E-mail'
            ))
            ->add('url', TextType::class,array(
                'label' => 'Homepage',
                'required' => false,
                'attr' => array(
                    'placeholder' => 'https://yandex.ua/',
                )
            ))
            ->add('text', TextareaType::class,array(
                'attr' => array(
                    'cols' => 30,
                    'rows' => 4,
                ),
            ))
            ->add('captcha', TextType::class, array(
                'label' => '',
                'mapped' => false,
                ))
            ->add('submit',SubmitType::class, array(
                'label' => 'Отправить',
            ))
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {

            $captcha = $form->get('captcha')->getData();
            $captcha = trim($captcha);
            $captcha = md5($captcha);

            if($captcha == $session->get('captcha',false))
            {
                $ip = $request->getClientIp();
                $userPost->setIp(ip2long($ip));
                $userPost->setDate(DateTime::createFromFormat('d.m.Y',Date('d.m.Y')));
                $userPost->setUserAgent($this->getUserBrowser());
                $userPost = $form->getData();
                $em = $this->getDoctrine()->getManager();
                $em->persist($userPost);
                $em->flush();
                return $this->redirectToRoute('guestbook');
            }
            else
            {
                $error = 'Not valid capthca';
            }


        }

        return $this->render('views/callbackForm.html.twig', array(
            'form' => $form->createView(),
            'error' => $error,
        ));
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
        $sql = "SELECT * FROM guest_book_table ORDER BY `name` ".$sort." LIMIT 0,25";
        $session->set('sortedField','name');
        $session->set('sortedMode',$sort);

        $response = $this->getValidJsonData($sql);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function getValidJsonData($sql)
    {
        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll();
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
        $sql = "SELECT * FROM guest_book_table ORDER BY Email ".$sort." LIMIT 0,25";
        $session->set('sortedField','email');
        $session->set('sortedMode',$sort);

        $response = $this->getValidJsonData($sql);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route ("OrderDate/{sort}", name="orderByDate", requirements={"sort" = "DESC|ASC"})
     */
    public function orderByDate($sort, SessionInterface $session)
    {
        $sql = "SELECT * FROM guest_book_table ORDER BY `date` ".$sort." LIMIT 0,25";
        $session->set('sortedField','date');
        $session->set('sortedMode',$sort);

        $response = $this->getValidJsonData($sql);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}