<?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    

    include("simple_html_dom.php");

    CONST TOKEN = '448d1a3d7c3e178da40642eed7e3de62aab37f9c03a829b4f7f2811fad6e3425b0a912f52e88111381599';
    CONST CONF_TOKEN = '30ec177d';
    $data = json_decode(file_get_contents('php://input'));

    
    //echo get_lessons('[headers=четверг]');
    
    switch ($data -> type) 
    {
        case 'confirmation':
            echo CONF_TOKEN;
        break;

        case 'message_new': 
            $message_text = $data -> object -> message -> text;
            $peer_id = $data -> object -> message -> peer_id;

            if (mb_substr($message_text, 0, 3) == "рнз") 
            {
                $message_text = get_lessons('[headers=четверг]');
                vk_msg_send($peer_id, $message_text);
            } 
            elseif (mb_substr($message_text, 0, 2) == "рн") 
            {
                $day = mb_substr($message_text, 3);
                $message_text = get_lessons('$day');
                vk_msg_send($peer_id, $message_text);
            } 

        break;

        default:
            
        break;
    }
    header("HTTP/1.1 200 OK");
    echo "ok";

    function vk_msg_send($peer_id, $text) 
    {
        $request_params = array(
            'message' => $text,
            'peer_id' => $peer_id,
            'access_token' => TOKEN,
            'random_id' => rand(0, 100000122),
            'v' => '5.131'
        );
        $get_params = http_build_query($request_params);
        file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
    }

    function get_lessons($day) 
    {
        
        $ls = parse_day(get_group("ИС317Д"), "$day");
        $message_text = "Пары на завтра: ";
        foreach($ls as $l) 
            {
                $message_text .= "\n";
                $message_text .= $l -> time .= " ";
                $message_text .= $l -> title;
            }
            
        return $message_text;
    }


    function get_group($str_group)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.ecol.edu.ru/timetable");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $out = curl_exec($ch);		
        curl_close($ch);

        $html = new simple_html_dom();
        $html -> load($out);
        $collection = $html -> find(".tablegroup-item a")[0];
        foreach($collection as $col) 
        {
            if ($col -> innertext == $str_group)
                $str = $col -> attr["href"];
        }
        
        return (string)$str;
    }
    
    function parse_day($href, $day) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $href);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $out = curl_exec($ch);		
        curl_close($ch);

        $html = new simple_html_dom();
        $html -> load($out);
        $collection = $html -> find("$day .timetable-lesson");
        
        
        //$collection = $col -> find('');
        //$inhtml = new DOMDocument();

        $str = "";
        $lessons[] = new lesson;

        foreach($collection as $colitem) 
        {
            $time = str_replace("&nbsp;", "", $colitem -> find(".date-display-single")[0] -> innertext);
            $title = str_replace("&nbsp;", "", $colitem -> find(".timetable-discipline")[0] -> innertext);
            $l = new lesson();
            $l -> title = $title;
            $l -> time = $time;
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