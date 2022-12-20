<?php
//require "./settings.php";

$bot_token = "5660869677:AAEgGN4W3FgaBkJovJwBrBmcZPBa6mmWTGA";

// получаем данные от телеграм
$data = json_decode(file_get_contents("php://input"));


$query = function ($method, $fields = []) use ($bot_token) {
   
    $ch = curl_init("https://api.telegram.org/bot" . $bot_token . "/" . $method);
   
    curl_setopt_array($ch, [
        CURLOPT_POST => count($fields),
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 10
    ]);
  
    $result = json_decode(curl_exec($ch), true);
 
    curl_close($ch);
   
    return $result;
};


$notice = function ($cbq_id, $text = null) use ($query) {

    $data = [
        "callback_query_id" => $cbq_id,
        "alert" => false
    ];
   
    if (!is_null($text)) {
        $data['text'] = $text;
    }
   
    $query("answerCallbackQuery", $data);
};


 
$getNumDayOfWeek = function ($date) {
   
    $day = $date->format("w");
    
    return ($day == 0) ? 6 : $day - 1;
};


$getDays = function ($month, $year) use ($getNumDayOfWeek) {
  
    $date = new DateTime($year . "-" . $month . "-01");

    $days = [];

    $line = 0;
   
    for ($i = 0; $i < $getNumDayOfWeek($date); $i++) {
        $days[$line][] = "-";
    }

    while ($date->format("m") == $month) {
   
        $days[$line][] = $date->format("d");
       
        if ($getNumDayOfWeek($date) % 7 == 6) {

            $line += 1;
        }

        $date->modify('+1 day');
    }
 
    if ($getNumDayOfWeek($date) != 0) {
        for ($i = $getNumDayOfWeek($date); $i < 7; $i++) {
            $days[$line][] = "-";
        }
    }

    return $days;
};


$viewCal = function ($month, $year, $chat_id, $cbq_id = null, $message_id = null) use ($getDays, $notice, $query) {

    $dayLines = $getDays($month, $year);
   
    $current = new DateTime($year . "-" . $month . "-01");
    
    $current_info = $current->format("m-Y");

    $buttons = [];
   
    $buttons[] = [
        [
            "text" => "<<<",
            "callback_data" => "cal_" . date("m_Y", strtotime('-1 month', $current->getTimestamp()))
        ],
        [
            "text" => $current_info,
            "callback_data" => "info_" . $current_info
        ],
        [
            "text" => ">>>",
            "callback_data" => "cal_" . date("m_Y", strtotime('+1 month', $current->getTimestamp()))
        ]
    ];

    foreach ($dayLines as $line => $days) {
    
        foreach ($days as $day) {
        
            $buttons[$line + 1][] = [
             
                "text" => $day,
              
                "callback_data" => $day > 0
                 
                    ? "info_" . $day . "-" . $current_info
                 
                    : "inline"
            ];
        }
    }
  
    $data = [
        "chat_id" => $chat_id,
        "text" => "<b>Календарь:</b>\n\n" . $current->format("F Y"),
        "parse_mode" => "html",
        "reply_markup" => json_encode(['inline_keyboard' => $buttons])
    ];
  
    if (!is_null($message_id)) {
      
        $notice($cbq_id);
        
        $data["message_id"] = $message_id;
      
        $query("editMessageText", $data);
    } else {
     
        $query("sendMessage", $data);
    }
};


$start = function($chat_id)  use($query) {
 
    $now_date = getdate();
    
    $buttons[][] = [
        "text" => "Открыть календарь",
        "callback_data" => "cal_" . $now_date['mon'] . "_" . $now_date['year']
    ];

    $data = [
        "chat_id" => $chat_id,
        "text" => "Привет, я бот которой подскажет вам о не внесенных часах в Timewebest + о внесенном времени",
        "parse_mode" => "html",
        "reply_markup" => json_encode(['inline_keyboard' => $buttons])
    ];

    $query("sendMessage", $data);
};


$getInfo = function($cbq_id, $chat_id, $message_id, $date) use($notice, $query) {
    define('MYSQL_SERVER', 'localhost');
    define('MYSQL_USER', 'newss9p4_time');
    define('MYSQL_PASSWORD','t&B1ett&');
    define('MYSQL_DB', 'newss9p4_time');
    $connect = mysqli_connect(MYSQL_SERVER, MYSQL_USER,MYSQL_PASSWORD,MYSQL_DB);
    $notice($cbq_id);
 
    $date_params = explode("-", $date);

    $buttons[][] = [
        "text" => "Вернуться",
        "callback_data" => "cal_" . $date_params[1] . "_" . $date_params[2]
    ];
    $text = "<b>Информация</b> по дате " . $date;
   
    //$text .= "\n---\nЕще данные для переменной \$text";
    $sql='SELECT name, ROUND((TIMESTAMPDIFF(minute, open_at, close_at) / 60), 0) as time_work FROM users,working_shifts WHERE users.id = user_id and DATE_FORMAT(working_shifts.open_at, "%d-%m-%Y") = "'.$date.'"';
    
if($result = mysqli_query($connect,$sql)) {
    
   foreach($result as $row) {
        $text .= "\n<b>".$row['name']. "</b>: ";
        if($row['time_work'] < 8) {
            $text .= "Не правильно заполнены данные";
        } else {
            $text .= $row['time_work']. " часов";
        }
   }
}
    $data = [
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $text,
        "parse_mode" => "html",
        "reply_markup" => json_encode(['inline_keyboard' => $buttons])
    ];
 
    $query("editMessageText", $data);
};



if (isset($data->message)) {
 
    $chat_id = $data->message->from->id;
   
    if (isset($data->message->text)) {
     
        if ($data->message->text == "/start") {
          
            $start($chat_id);
        }
    }

} elseif (isset($data->callback_query)) {
  
    $chat_id = $data->callback_query->from->id;
 
    $cbq_id = $data->callback_query->id;
  
    $c_data = $data->callback_query->data;

    $message_id = $data->callback_query->message->message_id;

    $params = explode("_", $c_data);

    if ($params[0] == "cal") {

        $viewCal($params[1], $params[2], $chat_id, $cbq_id, $message_id);
    }

    elseif ($params[0] == "info") {
  
        $getInfo($cbq_id, $chat_id, $message_id, $params[1]);
    }
 
    else {

        $notice($cbq_id, "This is notice for bot");
    }
}
