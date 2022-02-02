<?php

namespace App\Traits;



trait Image
{
    public $imageUrl = 'https://ui-avatars.com/api';


    public $colors = [
        'A'=>'ccc',
        'B'=>'d8f10b',
        'C'=>'81f3f0',
        'D'=>'3e07f8',
        'E'=>'60eb4b',
        'F'=>'cd0ce1',
        'G'=>'528d4b',
        'H'=>'118464',
        'I'=>'dd07e1',
        'J'=>'806595',
        'K'=>'a6f214',
        'L'=>'320ffd',
        'M'=>'b8682b',
        'N'=>'a5c1a7',
        'O'=>'5c7eca',
        'P'=>'d98158',
        'Q'=>'61540d',
        'R'=>'45da3b',
        'S'=>'46c58a',
        'T'=>'5db30e',
        'U'=>'275df4',
        'V'=>'d7cf9c',
        'W'=>'9651d8',
        'X'=>'0b8fa1',
        'Y'=>'00b82d',
        'Z'=>'31ef2a',
    ];

    public function createImageUrl(){
        try {
            $name = preg_replace('/ +/', '+', $this->full_name);
            $name = preg_replace('/(^\++)|(\++$)/', '', $name);

            return urldecode($this->imageUrl.'?'.http_build_query([
                    'name' => $name,
                    'background' => $this->colors[strtoupper($name[0])] ?? 'e5686d'
                ]));
        }
        catch (\Exception $e){
            return urldecode($this->imageUrl.'?'.http_build_query([
                    'name' => '',
                    'background' => 'e5686d'
                ]));
        }
    }


}
