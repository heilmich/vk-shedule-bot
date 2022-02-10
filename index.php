<?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include("simple_html_dom.php");

    CONST TOKEN = '448d1a3d7c3e178da40642eed7e3de62aab37f9c03a829b4f7f2811fad6e3425b0a912f52e88111381599';
    $conf_token = '30ec177d';
    $data = json_decode(file_get_contents('php://input'));

    parse_doc('http://www.ecol.edu.ru/timetable/fulltime/week/3840', '[headers=четверг]');

    
    switch ($data -> type) 
    {
        case 'confirmation':
            echo $conf_token;
        break;

        case 'message_new': 
            $message_text = $data -> object -> message -> text;
            $peer_id = $data -> object -> message -> peer_id;
            if (mb_substr($message_text, 0, 3) == "рнз") 
            {
                $ls = parse_doc('http://www.ecol.edu.ru/timetable/fulltime/week/3840', '[headers=четверг]');
                $str = "Пары на завтра: ";
                foreach($ls as $l) 
                {
                    $str .= "\n";
                    $str .= $l -> title;
                }
                vk_msg_send($peer_id, $str);
            } /* elseif (mb_substr($message_text, 0, 2) == "рн") 
            {
                $day = mb_substr($message_text, 3);
                $ls = parse_doc('http://www.ecol.edu.ru/timetable/fulltime/week/3840', "[headers=$day]");
                $str = "Пары на день перед $day: ";
                foreach($ls as $l) 
                {
                    $str .= "\n";
                    $str .= $l -> title;
                }
                vk_msg_send($peer_id, $str);
            } */

            

            echo 'ok';
        break;
    }
    

    function vk_msg_send($peer_id, $text) 
    {
        $request_params = array(
            'message' => $text,
            'peer_id' => $peer_id,
            'access_token' => TOKEN,
            'random_id' => rand(0, 100000),
            'v' => '5.131'
        );
        $get_params = http_build_query($request_params);
        file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
    }
    
    function parse_doc($href, $day) 
    {
        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $href);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $out = curl_exec($ch);		//помещаем html-контент в строку
        curl_close($ch);

        $html = new simple_html_dom();
        $html -> load($out);
        $collection = $html -> find("$day [class=timetable-discipline]");
        
        //$collection = $col -> find('');
        //$inhtml = new DOMDocument();

        $str = "";
        
        foreach($collection as $colitem) 
        {
            $str = str_replace("&nbsp;", "", $colitem -> innertext);
            $l = new lesson();
            $l -> title = $str;
            $lessons[] = $l;
            
            //$inhtml -> appendChild($inhtml -> importNode($colitem, true));
        }

        return $lessons;
    }

    class lesson 
    {
        public $day;
        public $time;
        public $title;
        public $teacher;
    }
?>