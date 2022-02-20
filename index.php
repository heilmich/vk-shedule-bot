<?php 
    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);

    include("simple_html_dom.php");

    $dbcontext = mysqli_connect("localhost","a0631012_vkbot", "njnjhj", "a0631012_vkbot");
    mysqli_set_charset($dbcontext, "utf8");

    CONST TOKEN = '448d1a3d7c3e178da40642eed7e3de62aab37f9c03a829b4f7f2811fad6e3425b0a912f52e88111381599';
    CONST CONF_TOKEN = '645f59f9';
    CONST SECRET_KEY = "secrt";
    
    $weekdays =    [0 => new WeekDay(0, "воскресенье", "вс", "воскресенье"),
                    1 => new WeekDay(1, "понедельник", "пн", "понедельник"),
                    2 => new WeekDay(2, "вторник", "вт", "вторник"),
                    3 => new WeekDay(3, "среда", "ср", "среду"),
                    4 => new WeekDay(4, "четверг", "чт", "четверг"),
                    5 => new WeekDay(5, "пятница", "пт", "пятницу"),
                    6 => new WeekDay(6, "суббота", "сб", "субботу")];

    $WeekDays =   [0 => "воскресенье", 
                    1 => "понедельник",
                    2 => "вторник",
                    3 => "среда",
                    4 => "четверг",
                    5 => "пятница",
                    6 => "суббота"];

    $week_id =     ["воскресенье" => 0, 
                    "понедельник" => 1,
                    "вторник" => 2,
                    "среда" => 3,
                    "четверг" => 4,
                    "пятница" => 5,
                    "суббота" => 6];

    $commands =    ["рн" => new ChatCommand(".рн", "выводит расписание на день недели. Пример: .рн вторник | вторник.рн | .рн вт | вт.рн", false), // расписание на 
                    "рнc" => new ChatCommand(".рнс", "выводит расписание на сегодня.", false), // расписание на cегодня 
                    "рнз" => new ChatCommand(".рнз", "выводит расписание на завтра.", false), // расписание на завтра
                    "almaz" => new ChatCommand("алмаз", "алмаз пидор.", true),
                    "add_almaz" => new ChatCommand("добавитьалмаза", "алмаз пидор.", true),
                    "change_group" => new ChatCommand(".сменагр", "сменить группу. Пример: .сменагр ис317д, .сменагр ГД228Д", false),
                    "list_commands" => new ChatCommand(".команды", "список команд.", false),
                    "hello" => new ChatCommand(".привет", "выводит приветственное сообщение бота.", false)];



    
    $data = json_decode(file_get_contents('php://input'));
    
    if ($data -> secret == SECRET_KEY) 
    {
        switch ($data -> type) 
        {
            case 'confirmation':
                echo CONF_TOKEN;
            break;

            case 'message_new': 
                
                $message_text = $data -> object -> message -> text;
                $peer_id = $data -> object -> message -> peer_id;
                $msg = mb_strtolower($message_text);

                if ($dbcontext == false) 
                    return;
                
                if (mb_strripos($msg, $commands["рнз"] -> command) !== false)
                {
                    $msg = get_lessons_week(date('w')+1, $peer_id);
                    if (empty($msg)) $msg = "Не получилось получить расписание";
                    vk_msg_send($peer_id, $msg);
                } 

                elseif (mb_strripos($msg, $commands["рнc"] -> command) !== false) 
                {
                    $msg = get_lessons_week(date('w'), $peer_id);
                    if (empty($msg)) $msg = "Не получилось получить расписание";
                    vk_msg_send($peer_id, $msg);
                } 

                elseif (mb_strripos($msg, $commands["рн"] -> command) !== false) 
                {
                    $id = ($msg);
                    if ($id == null) 
                    {
                        vk_msg_send($peer_id, "День недели не найден. Возможно, вы неправильно его указали.");
                        header("HTTP/1.1 200 OK");
                        send_ok();
                        return;
                    }
                    $msg = get_lessons_week($id, $peer_id);
                    vk_msg_send($peer_id, $msg);
                } 

                elseif ($msg === $commands["almaz"] -> command) 
                {
                    $r = rand(1, 3);
                    $msg = get_almaz_words($peer_id);
                    vk_msg_send($peer_id, $msg);
                } 

                elseif (mb_strripos($msg, $commands["add_almaz"] -> command) !== false) 
                {
                    $word = trim(mb_substr($msg, 14));
                    $result = add_almaz_word($peer_id, $word);
                    if ($result == false) $msg = "Не удалось добавить $word";
                    else $msg = "Фраза успешно добавлена [$word]";
                    vk_msg_send($peer_id, $msg);
                }

                elseif ($msg === $commands["list_commands"] -> command) 
                {
                    $msg = "";
                    foreach ($commands as $c) 
                    {
                        if ($c -> isSecret == false)
                        $msg .= $c -> command . " - " . $c -> title . "\n";
                    }
                    vk_msg_send($peer_id, $msg);
                } 

                elseif (mb_strripos($msg, $commands["change_group"] -> command) !== false)
                {
                    //$start = mb_stripos($msg, $commands["change_group"] -> command);
                    //$end = mb_strripos($msg, $commands["change_group"] -> command);
                    $group = trim(mb_strtoupper(mb_substr($msg, 8)));
                    if (empty($group) || $group == "") 
                    {
                        $msg = "Вы не указали группу. Используйте '" . $commands["change_group"] -> command . "' название_группы";
                    }
                    $result = change_group($peer_id, $group);
                    if ($result == true) $msg = "Группа успешно обновлена [$group]" ;
                    else ($msg = "Не удалось обновить на $group" . mysqli_connect_error());
                    vk_msg_send($peer_id, $msg);
                } 

                elseif (mb_strripos($msg, $commands["hello"] -> command) !== false || $data -> object -> message -> action -> type == "chat_invite_user")
                {
                    $msg = "Привет, я бот расписания ЮУГК. Для того, чтобы назначить группу, напишите: " . $commands["change_group"] .
                           "\nДля отображения всех команд используйте" . $commands["list_commands"];
                }

                header("HTTP/1.1 200 OK");
                send_ok();
            break;

            default:
                header("HTTP/1.1 200 OK");
                send_ok();
            break;
        }
    }
    
    function send_ok() {
        set_time_limit(0);
        ini_set('display_errors', 'Off');
    
        // для Apache
        ignore_user_abort(true);
    
        ob_start();
        header('Content-Encoding: none');
        header('Content-Length: 2');
        header('Connection: close');
        echo 'ok';
        ob_end_flush();
        flush();
        return True;
    }

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

    function db_query($query) 
    {
        global $dbcontext;
        $result = mysqli_query($dbcontext, $query);
        return $result;
    }

    //получает расписание из parse_day и группирует его в строку
    function get_lessons_week($dayid, $peer_id) 
    {
        global $weekdays;

        $group = mysqli_fetch_object(db_query("SELECT * FROM Conversation WHERE peer_id = '$peer_id'"));

        $day = $weekdays[$dayid + 1] -> name;
        $href = get_group_shedule($group -> title);
        $ls = parse_day($href, "$day");

        if ($ls == false) return "Занятий не найдено";

        $d = $weekdays[$dayid] -> parname; 
        $msg = "Пары на $d: ";

        for($i = 1; $i < count($ls); $i++) 
            {
                $msg .= "\n";
                $msg .= "[" . $ls[$i] -> time . "] ";
                $msg .= $ls[$i] -> title;
            }
        return $msg;
    }

    // заходит на общую страницу расписаний, ищет название str_group в элементах "a" и переходит по ссылке
    function get_group_shedule($str_group)
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
        $collection = $html -> find(".tablegroup-item a");
        foreach($collection as $col) 
        {
            if ($col -> innertext == $str_group)
            {
                $str = "http://www.ecol.edu.ru";
                $str .= $col -> href;
            }
        }
        
        return (string)$str;
    }


    function add_almaz_word($peer_id, $word) 
    {
        $query = "INSERT INTO AlmazWords SET peer_id = '$peer_id', word = '$word'";
        $result = db_query($query);

        if ($result == false) 
            return false;

        return true;
    }

    function get_almaz_words($peer_id) 
    {
        //возвращает одно случайное слово
        $query = "SELECT * FROM AlmazWords WHERE peer_id = '$peer_id' ORDER BY RAND() LIMIT 1";
        $result = mysqli_fetch_object(db_query($query));

        if ($result == false) 
            return false;

        return $result -> word;
    }

    //ищет день в коллекции дней weekdays
    function find_day($str)  
    {
        global $weekdays;

        foreach($weekdays as $d) 
        {
            if (mb_stripos($str, $d -> name) !== false || mb_stripos($str, $d -> shortname) !== false || mb_stripos($str, $d -> parname) !== false) 
            { 
                return $d -> id;
            }
        }

        //если день не найден
        return null;
    }

    function my_substr($str, $start, $end)
    {
        return mb_substr($str, $start, $end - $start);
    }
    
    // собираеn данные со страницы расписания группы
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
        $collection = $html -> find("[headers=$day] .timetable-lesson");
        
        $lessons[] = new Lesson;

        foreach($collection as $colitem) 
        {
            $time = str_replace("&nbsp;", "", $colitem -> find(".date-display-single")[0] -> innertext);
            $title = str_replace("&nbsp;", "", $colitem -> find(".timetable-discipline")[0] -> innertext);
            $l = new Lesson();
            $l -> title = $title;
            $l -> time = $time;
            $lessons[] = $l;
        }
        if ($lessons == null) return false;

        return $lessons;
    }

    function change_group($peer_id, $strgroup) 
    {
        $strgroup = trim($strgroup, " ");
        if (mysqli_fetch_row(db_query("SELECT EXISTS(SELECT id FROM Conversation WHERE peer_id = '$peer_id')"))[0] > 0)
        {
            $sqlupd = "UPDATE Conversation SET peer_id = '$peer_id', title = '$strgroup' WHERE peer_id = '$peer_id'";
            $result = db_query($sqlupd);
            if ($result == false) 
            {
                return false;
            }
        }
        else {
            $sqlins = "INSERT INTO Conversation SET peer_id = '$peer_id', title = '$strgroup'";
            $result = db_query($sqlins);
            if ($result == false) 
            {
                return false;
            }
        }
        return true;
    }

    class ChatCommand 
    {
        public $command;
        public $title;
        public $isSecret;

        function __construct($com, $tit, $isSecret) 
        {
            $this -> command = $com;
            $this -> title = $tit;
            $this -> isSecret = $isSecret;
        }
    }

    class WeekDay 
    {
        public $id;
        public $name; // название дня недели
        public $shortname; // сокращенное название
        public $parname; // родительский падеж

        function __construct($id, $name, $shortname, $parname) 
        {
            $this -> id = $id;
            $this -> name = $name;
            $this -> shortname = $shortname;
            $this -> parname = $parname;
        }
    }

    class Lesson 
    {
        public $day;
        public $time;
        public $title;
        public $teacher;
    }
?>