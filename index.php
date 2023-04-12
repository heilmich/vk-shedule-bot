<?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $dbcontext = mysqli_connect("localhost","bitrix0", "O?EVX}v9oS8P!YIs219n", "sitemanager");
    
    mysqli_set_charset($dbcontext, "utf8");

    CONST TOKEN = '448d1a3d7c3e178da40642eed7e3de62aab37f9c03a829b4f7f2811fad6e3425b0a912f52e88111381599';
    CONST CONF_TOKEN = 'c613ccc5';
    CONST SECRET_KEY = "secrt";

    $data = json_decode(file_get_contents('php://input'));

    $commands =    ["almaz" => new ChatCommand("алмаз", "алмаз пидор.", true),
                    "add_almaz" => new ChatCommand("добавитьалмаза", "алмаз пидор.", true),
                    "list_commands" => new ChatCommand(".команды", "список команд.", false),
                    "hello" => new ChatCommand(".привет", "выводит приветственное сообщение бота.", false)];

    
    if ($data -> secret == SECRET_KEY) 
    {
        switch ($data -> type) 
        {
            case 'confirmation':
                echo CONF_TOKEN;
            break;

            case 'message_new': 
                send_ok();
                $message_text = $data -> object -> message -> text;
                $peer_id = $data -> object -> message -> peer_id;
                $msg = mb_strtolower($message_text);

                if ($dbcontext == false) 
                    return;
            
                if (trim($msg) === $commands["almaz"] -> command) 
                {
                    $r = rand(1, 3);
                    $msg = get_almaz_words($peer_id);
                    vk_msg_send($peer_id, $msg);
                } 

                elseif (mb_strripos($msg, $commands["add_almaz"] -> command) !== false) 
                {
                    $word = trim(mb_substr($msg, count($commands['add_almaz'] -> command)));
                    $result = add_almaz_word($peer_id, $word);
                    if ($result == false) $msg = "Не удалось добавить $word";
                    else $msg = "Фраза успешно добавлена [$word]";
                    vk_msg_send($peer_id, $msg);
                }

                elseif (trim($msg) === $commands["list_commands"] -> command) 
                {
                    $msg = "";
                    foreach ($commands as $c) 
                    {
                        if ($c -> isSecret == false)
                        $msg .= $c -> command . " - " . $c -> title . "\n";
                    }
                    vk_msg_send($peer_id, $msg);
                } 

                elseif (mb_strripos($msg, $commands["hello"] -> command) !== false || $data -> object -> message -> action -> type == "chat_invite_user")
                {
                    $msg = "Привет, я алмазный бот. Восстал из ада, чтобы послать алмаза нахуй." .
                           "\nДля отображения всех команд используйте" . $commands["list_commands"];
                }
            break;

            default:
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
        header("HTTP/1.1 200 OK");
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

    function initialize_tables() {
        $query = "CREATE TABLE IF NOT EXISTS AlmazWords (peer_id INT NOT NULL, word VARCHAR(4000) NOT NULL)";
        $result = db_query($query);

        return $result !== false;
    }

    function add_almaz_word($peer_id, $word) 
    {
        $query = "INSERT INTO AlmazWords SET peer_id = '$peer_id', word = '$word'";
        $result = db_query($query);

        return $result !== false;
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


    function my_substr($str, $start, $end)
    {
        return mb_substr($str, $start, $end - $start);
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
?>