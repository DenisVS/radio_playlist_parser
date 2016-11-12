<?php
//0       */3     *       *       *       cd /cygdrive/c/PF/tasks/playlist/  && /usr/bin/svn up && cp /cygdrive/c/PF/tasks/playlist/radio.xspf /cygdrive/c/PF/tasks/ && chmod 777 /cygdrive/c/PF/tasks/radio.xspf

require('phpQuery/phpQuery.php');


function unique_array_by_key($array, $key) { 
    $temp_array = array(); 
    $i = 0; 
    $key_array = array(); 
    foreach($array as $val) { 
        if (!in_array($val[$key], $key_array)) { 
            $key_array[$i] = $val[$key]; 
            $temp_array[$i] = $val; 
        } 
        $i++; 
    } 
    return $temp_array; 
}


$text0=file_get_contents('http://echo.msk.ru/interview/');
sleep(2);
$text1=file_get_contents('http://echo.msk.ru/top/programs.html');
$text = $text1.$text0;


$permittedNames=file_get_contents('./permitted_names.txt');

$permittedNames = file('./permitted_names.txt');

// Покормим phpQuery кодом страницы
$document = phpQuery::newDocument($text);

// Выберем элементы
$elements = $document->find('div.prevcontent');
//echo $elements;
$num = 0;
// Пробегаем по найденым элементам
foreach ($elements as $element){
	//var_dump ($element);
	//echo pq($element)->find('strong.name');
	$about = pq($element)->find('span.about');
	//echo $about."\n";
	$name = pq($about)->find('strong.name')->text();
	//echo $name."\n";
	$post = pq($about)->find('a')->text();
	//echo $post."\n";

$permitted = FALSE;

	if ($name != "") {
		echo "Name=".$name."\n";
		// Блок проверки допустимых имён
		foreach ($permittedNames as $line_num => $line) {
			if (trim($line) == $name) {
				echo "Гость " . $line . " подходит!\n";
				$permitted = TRUE;
			} 
		}
		
		
		$program[$num]['names'][] = $name;
	}
	if ($post != "") {
		//echo "Post=".$post."\n";
		$names = explode("\n", trim($post));
		foreach ($names as $name) {
			echo "Name=".$name."\n";
				// Блок проверки допустимых имён
				foreach ($permittedNames as $line_num => $line) {
					if (trim($line) == $name) {
						echo "Гость " .$line . " подходит!\n";
						$permitted = TRUE;
					}
				}
			$program[$num]['names'][] = $name;
		}
	}
	if ($permitted == FALSE) {
		unset ($program[$num]);  //Исключаем, если нет разрешённых имён
		echo "Одни мудаки!\n";
	}
	// Выяснить тип и рубрику
	$section = pq($element)->find('div.section');
	//echo $section."\n";
	$red = pq($section)->find('a.red');
	$type = pq($red)->find('strong')->text();

	$lite = pq($section)->find('a.lite');
	$rubric = pq($lite)->find('span')->text();

	if ($rubric == "") {
		$rub = pq($section)->find('a');
		$rubric = pq($rub)->find('strong')->text();
	}
	echo $type. " - " .$rubric."\n";
	$program[$num]['rubric'] = $rubric;
	//
	$mediamenu = pq($element)->find('div.mediamenu');
	//echo $mediamenu."\n";
	$mp3 = pq($mediamenu)->find('a.download')->attr('href');
	echo "mp3 ".$mp3."\n";
	$program[$num]['mp3'] = $mp3;
	///////////////////
	$datetime = pq($element)->find('span.datetime')->attr('title');
	$program[$num]['datetime'] = $datetime;
	echo "num=".$num."\n";
	echo "\n----------- Разделитель элементов -------------\n";
	if (isset ($program[$num]['names'][0])  && isset ($program[$num]['mp3']) && ($program[$num]['mp3'] != "")) {
		$num = $num+1;
	} else {
		unset($program[$num]);
	}
}

$program = unique_array_by_key($program, 'mp3');
$program = array_values($program);

// Спарсили, далее формируем XML из шаблона
$template[] = 'CUSTOM_TITLE_1';
$template[] = 'CUSTOM_TITLE_2';
$template[] = 'CUSTOM_TITLE_3';
$template[] = 'CUSTOM_TITLE_4';
$template[] = 'CUSTOM_TITLE_5';
$template[] = 'CUSTOM_TITLE_6';
$template[] = 'CUSTOM_TITLE_7';
$template[] = 'CUSTOM_TITLE_8';
$template[] = 'CUSTOM_TITLE_9';
$template[] = 'CUSTOM_TITLE_10';

$doc = new DOMDocument();
$doc->load( 'template.xspf' );
$trackList = $doc->getElementsByTagName( "track" );
$xml = simplexml_load_file('template.xspf');
$num = 0;
foreach( $template as $temp )	{
		
	echo $temp . "\n";
	$n = 0;
	foreach( $trackList as $track )	{
		$names = $track->getElementsByTagName( "title" );
		$name = $names->item(0)->nodeValue;
		//echo "$name - $n \n";
		if ($temp == $name) {
			$numberOfItem = $n ;
		}
		$n = $n + 1;
	}
	
	echo $numberOfItem . "-----------\n";
	$outNames = implode(', ', $program[$num]['names']);
	echo "Out ".$outNames."\n";
	//$xml->trackList->track[$numberOfItem]->title = $program[$num]['names'][0]." - ".$program[$num]['rubric'];
	$xml->trackList->track[$numberOfItem]->title = $outNames." (".$program[$num]['rubric']." ".$program[$num]['datetime'].")";
	$xml->trackList->track[$numberOfItem]->location = $program[$num]['mp3'];
	$num = $num+1;
}	

$xml->asXML('/data/playlist/data/radio.xspf');

?>
