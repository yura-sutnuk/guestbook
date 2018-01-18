<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Flex\Response;

class captcha
{
    public function generateCode()
    {
        $chars = 'abdefhknrstyz23456789';
        $length = rand(3,7);
        $numChars = strlen($chars);
        $str = '';
        for($i=0;$i<$length;$i++)
        {
            $str .= substr($chars, rand(1, $numChars-1), 1);
        }
        $array_mix = preg_split('//',$str,-1,PREG_SPLIT_NO_EMPTY);
        shuffle($array_mix);
        $str = implode('',$array_mix);

        return $str;
    }

    /**
     * @Route ("captcha", name = "captcha")
     */
    public function createCaptcha(SessionInterface $session)
    {
        header('Content-Type: image/png');
        $captcha = $this->generateCode();
        $session->set('captcha',md5($captcha));
        $linenum = rand(3,7);
        $font_array = [];
        $font_array[0]['name'] = 'DroidSans.ttf';
        $font_array[0]['size'] = rand(20,30);
        $fontNo = rand(0,sizeof($font_array)-1 );
        $img = imagecreate(150,70);
        $color = imagecolorallocate($img,117,117,117);
        $color = imagecolorallocate($img,0,0,0);
        for($i=0;$i<$linenum;$i++)
        {
            imageline($img,rand(0,150),rand(0,70),rand(0,150),rand(0,70),$color);
        }

        $x = rand(0,35);
        for($i=0;$i<strlen($captcha);$i++)
        {
            $x +=15;
            $letter = substr($captcha,$i,1);
            imagettftext($img,$font_array[$fontNo]['size'],rand(2,4),$x,rand(50,55),$color,$font_array[$fontNo]['name'],$letter);
        }

        for($i=0;$i<$linenum;$i++)
        {
            imageline($img,rand(0,150),rand(0,70),rand(0,150),rand(0,70),$color);
        }
        imagepng($img);
        imagedestroy($img);

    }
}

