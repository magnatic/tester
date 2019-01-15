<?php
sleep(0);
if(!isset($_GET['ajax'])){ header('Location: http://domkihot.ru/'); }

//Собираем статистику по ошибке 404
if(isset($_GET['analize_log_404'])){
    ?>
<style>
.h1-style{
    text-align:center;
    font-size:18px;
}
.small{ font-size: 80%; }
.table{ border-collapse:collapse; width:100%; }
.table,
.table th,
.table td{
    border:1px solid #999;
}
.table td{ text-align:center; }
.table td:first-child{ text-align:left; width:350px; }
.table td:last-child{ text-align:right; }
.m-width{ max-width:350px; }
</style>
    <?php
    $result = send_analytics();
    $html = '<h1 class="h1-style">Анализ протокола 404 ошибок по состоянию на '.date('d/m/Y').'</h1>';
    $html.= '<table class="table"><tr>
        <th>URL</th>
        <th>Попаданий</th>
        <th>ip адреса</th>
        <th>headers</th>
        <th>Последний визит</th>
        </tr>';
    foreach ($result as $key => $row) {
        $html.= '<tr>
            <td>'.((strripos($row['url'],'?') === false)?$row['url']:stristr($row['url'], '?', true)).'</td>
            <td>'.$row['count'].'</td>
            <td class="small">'.implode('<br>',$row['ip']).'</td>
            <td class="small m-width" style="text-align:left;">&raquo; '.implode('<br>&raquo; ',$row['user_agent']).'</td>
            <td>'.$row['last_visit'].'</td>
            </tr>';
    }
    $html.= '</table>';
    //var_export($result);
    echo $html;
}

if(isset($_GET['img'])){
    echo '<img src="/uploads/'.$_GET['img'].'">';
    exit;
}

if(isset($_GET['target'])){
    $getPage=array();
    $getPage['url'] = (isset($_GET['url']))
        ? filterStr($_GET['url'])
        : 'main';
    
    $getPage['catalog'] = (isset($_GET['catalog']))
        ? filterStr($_GET['catalog'])
        : false;
    
    //Получаем массив данных о странице
    $config['page'] = getPageData($getPage);
    load_page_kernel($config['page']['url']);
}

if(isset($_GET['sms_event'])){
    //Настройки сообщений по умолчанию
    $message=array(
        'phone'=>'89263635655', //кому отправляем смс по умолчанию
        'sms_text'=>'Обратный звонок
'.$_POST['name'].'
'.$_POST['phone'].'
'.$_POST['message'].''
    );
    //отправляем смс
    if(!empty($_POST['phone'])){
        sms(array('phone'=>$message['phone'],'text'=>$message['sms_text']));
    }
}

/*/показать видео
if(isset($_GET['video'])){	
	$module_video=mysql_fetch_assoc(query("SELECT * FROM `module_video` WHERE `url`='".mysql_real_escape_string($_GET['video'])."' LIMIT 1"));
	if($module_video['frame']!=''){
		echo $module_video['frame'];
	exit;
	}
	$video_source=array();
	if(is_file($_SERVER['DOCUMENT_ROOT'].'/uploads/video/'.$module_video['url'].'.ogv')){ $video_source[]=array( 'ext'=>'ogv', 'type'=>'video/ogg; codecs="theora, vorbis"' ); }
	if(is_file($_SERVER['DOCUMENT_ROOT'].'/uploads/video/'.$module_video['url'].'.webm')){ $video_source[]=array( 'ext'=>'webm', 'type'=>'video/webm; codecs="vp8, vorbis"' ); }
	if(is_file($_SERVER['DOCUMENT_ROOT'].'/uploads/video/'.$module_video['url'].'.mp4')){ $video_source[]=array( 'ext'=>'mp4', 'type'=>'video/mp4; codecs="avc1.42E01E, mp4a.40.2"' ); }


?>
<!DOCTYPE html>
<html>
	<body>
	<!--	<video width="840" preload controls autoplay id="<?=$module_video['ID']; ?>"> </video>-->
		<video width="840" preload autoplay="autoplay" controls="controls" tabindex="0" id="<?=$module_video['ID']; ?>">
			<?php
			foreach ($video_source as $source) {
				echo '<source src="'.$config['site_url'].'/uploads/video/'.$module_video['url'].'.'.$source['ext'].'" type="'.$source['type'].'">';
			}
			?>
			<p>Your browser does not support the video tag.</p>
		</video>

	</body>
</html>
<?php
}//*/


//Открыть страницу (любую в ajax)
if(isset($_GET['page'])){
    if(!is_file($_SERVER['DOCUMENT_ROOT'].'/pages/'.$_GET['page'].'.php')){ die('Нельзя так');}
    include($_SERVER['DOCUMENT_ROOT'].'/pages/'.$_GET['page'].'.php');
}


/*/показать видео из youtube
if(isset($_GET['youtube'])){ ?>
<iframe width="700" height="450" src="//www.youtube.com/embed/<?=$_GET['vlink']; ?>?rel=0&amp;controls=0&amp;showinfo=0&amp;autoplay=1" frameborder="0" allowfullscreen></iframe>
<?php flog('Загружаем видео из YOUTUBE: '.$_GET['vlink']); }//*/

/*/НОВЫЙ КОММЕНТАРИЙ
if(isset($_GET['newComment'])){
	include($_SERVER['DOCUMENT_ROOT']."/module/comments/comment.class.php");

	$name=mysql_real_escape_string($_POST['CommentName']);
	$email=mysql_real_escape_string($_POST['CommentMail']);
	$message=mysql_real_escape_string($_POST['CommentMessage']);
	$url=mysql_real_escape_string($_POST['url']);
	$refer=$_SERVER['HTTP_REFERER'];

	if($_POST['no_fill_captcha']!=''){ echo 'Да вы спамер!'; flog('comment: '.$name,'spam'); die('Похоже на спам'); }
	if(strpos('http://', $message)){ echo 'Ссылки запрещены!'; flog('comment: '.$name,'spam'); die('Похоже на спам'); }
	
	$sql="INSERT INTO `comments` (`status`,`name`, `email`, `comment`, `url`)
	VALUES ('1','".$name."', '".$email."', '".$message."', '".$url."')";
	
	if(!query($sql)){ flog($sql,'error'); die("Система комментариев временно не доступна!"); }

	$last_id = mysql_insert_id();
	$newCommentData = new Comment(mysql_fetch_assoc(query("SELECT * FROM comments WHERE `ID`='".$last_id."' LIMIT 1")));
	echo $newCommentData->markup();
	
	$mailData=array(
		'to'=>'it.gabaraev@domkihot.ru',
		'subject'=>'Новый комментарий',
		'message'=>'<p>Оставлен комментарий от <strong>'.$name.'</strong> на странице <a href="'.$refer.'">'.$refer.'</a> :</p>
			<p><strong>Сообщение:</strong> '.$message.'</p>
			<p><strong>Контакты клиента:</strong> '.$email.'</p>'
	);
    $send=decode(email_send($mailData));
	if(!$send['status']){ flog($mailData,'error'); }
	exit;
}//*/


if(isset($_GET['unsubscribe'])){
	$mailData = array(
       'to' => 'it@domkihot.ru',
       'subject' => 'Отписаться от рассылки',
       'message' => $_GET['unsubscribe']
	);
	@send_email($mailData);
	include($_SERVER['DOCUMENT_ROOT'].'/template/unsubscribe.php');
}


//Формы обратной связи
if(isset($_GET['form'])){
    include($_SERVER['DOCUMENT_ROOT'].'/template/form.'.$_GET['form'].'.php');
}
if(isset($_GET['saveForm'])){
    include($_SERVER['DOCUMENT_ROOT'].'/module/saveForm.php');
}


//поиск проектов (по фильтру)
if(isset($_GET['search'])){
    include($_SERVER['DOCUMENT_ROOT'].'/pages/projects.php');
}
//Проекты. Избранное. Управление
if(isset($_GET['changeFavorites'])){
	echo favorites( 'changeFavorites', array('projectID'=>$_GET['projectID']) );
	exit;
}


if(isset($_GET['download_pdf'])){

    $projID=intval($_GET['download_id']);
    $project_data=getProjectData($projID);

    //$project_data=$project_data[0];

    $project_data['image_main']=$config['site_url'].$project_data['photo'][0];
    if(isset($project_data['photo'][1])){
    $project_data['image_main2']='<img src="'.$config['site_url'].'/uploads/projects/_big/'.$project_data['photo'][1].'" style=" position:absolute; top:0; left:-5mm; min-width:95mm; min-height:60mm; max-width:100mm;">'; }
    $project_data['image_main3']=(isset($project_data['photo'][2]))?'<img src="'.$config['site_url'].'/uploads/projects/_big/'.$project_data['photo'][2].'" style=" position:absolute; top:0; left:-5mm; min-width:95mm; min-height:60mm; max-width:100mm;">':'';

    $project_data['image_plan']=$config['site_url'].$project_data['plan'][0];
    $project_data['image_plan2']=$config['site_url'].$project_data['plan'][1];

    //$project_data['p_change']='от '.ceil($project_data['set_area']*0.85).' до '.ceil($project_data['set_area']*1.16).' м<sup>2</sup>';

    //$project_data['construction']=countWorkDay($project_data['set_area']);
    $project_data['contacts_map']='/img/contacts_map.jpg';

    $project_data['p_dimension']=$project_data['p_dimension1'].' м х '.$project_data['p_dimension2'].' м';

    $project_data['year_long'] = date('Y')-2005;

    $html = implode('', file(__DIR__.'/core/modules/download_pdf.inc.php'));
    foreach($project_data as $name=>$value){ 
        if(is_array($value)){ $value=encode($value); }
        $html=str_replace(":".$name.":", $value, $html); }
    echo $html;
}
    

//Отправка из новой формы (скидки и акции)(Задать вопрос)
if(isset($_GET['send_message'])){
$mailData = array(
	'to' => array('it.gabaraev@domkihot.ru','to@domkihot.ru'),
	'subject' => 'Со страницы акции', 
	'message' => mysql_real_escape_string($_POST['m_email']).'<br><br>Вопрос:'.mysql_real_escape_string($_POST['m_message'])
	);
	$send=decode(email_send($mailData));
    if(!$send['status']){ flog($mailData,'error'); }
	
	$mailDataU = array(
		'to' => $_POST['m_email'],
		'subject' => 'Новогодняя акция', 
		'message' => 'Здравствуйте!<br>Вы оставили вопрос на сайте компании ДомКихот <a href="http://domkihot.ru/" target="_blank">http://domkihot.ru</a>:<br><i>'.mysql_real_escape_string($_POST['m_message']).'</i><br><br>Наш специалист ответит Вам в ближайшее время!'
	);
	$send=decode(email_send($mailData));
    if(!$send['status']){ flog($mailData,'error'); }
echo print_noty(array('type'=>'notice','title'=>'Ваше сообщение отправлено!','text'=>'Ответ Вы получите на указанный email <strong>'.$iData['email'].'</strong> в ближайшее время!'));
}

/*/Нашли ошибку на сайте?
if(isset($_GET['orphus'])){
	$mailData = array(
		'to' => 'no-reply@domkihot.ru',
		'subject' => 'Нашли ошибку ctrl+enter', 
		'message' => 'Какой то тип нашел ошибку: <br><br><b>'.mysql_real_escape_string($_POST['message']).'</b><br><br>На странице '.mysql_real_escape_string($_POST['page']), 
	);
	$send=decode(email_send($mailData));
    if(!$send['status']){ flog($mailData,'error'); }
	exit;
}//*/

/*/Обновляем панель корзины
if(isset($_GET['refBasket'])){
	include($_SERVER['DOCUMENT_ROOT'].'/module/basket.inc.php');
exit;
}//*/

//Распечатываем таблицу сравнения
if(isset($_GET['printCompare'])){
	echo '<link href="'.$config['site_url'].'/styles/main.css?v=2.0" rel="stylesheet" type="text/css" />
	<link href="'.$config['site_url'].'/styles/page.css?v=2.0" rel="stylesheet" type="text/css" />';
	include($_SERVER['DOCUMENT_ROOT'].'/pages/compare.php');
    
    $mailData = array(
        'to' => array('it.gabaraev@domkihot.ru'),
        'subject' => 'Распечатывают сравнение проектов', 
        'message' => implode('<br>', decode($_COOKIE['favorite']))
    );
    $send=decode(email_send($mailData));
    if(!$send['status']){ flog($mailData,'error'); }
	exit; 
}



/* Обработка технических неисправностей */
if(isset($_GET['projects'])){ $projects=array();
    $query="SELECT * FROM `projects` WHERE `status`=1 ORDER BY `p_name`";
    $ret=query($query);
    while($project=mysql_fetch_assoc($ret)){
        $floor1=explode('<br/>', $project['d_floor1']);
        $str=array();
        foreach($floor1 as $des1){
            $str[]= preg_match_all("/\d/", '', $des1);
            
        }
        $project['floor1']=implode(' / ',$str);
        $project['price_base']=round($project['price_base'], -3);
        //query("UPDATE `projects` SET `price_base`='".$project['price_base']."' WHERE `ID`='".$project['ID']."' LIMIT 1");
        $projects[]=array(
            'ID'=>$project['ID'],
            'group'=>$project['group'],
            'title'=>$project['p_name'],
            'p_set_area'=>$project['p_set_area'],
            'dimension'=>$project['p_dimension1'].' м x '.$project['p_dimension2'].' м',
            'floor1'=>$project['floor1'],
            'price'=>number_format(intval($project['price_base']),0,'.',' ').' руб'
        );
    }

    echo '<table border=1>';
    foreach($projects as $pr){
        echo '<tr>';
        foreach($pr as $key=>$val){
            echo '<td>'.$val.'</td>';
        }
        echo '</tr>';   
    }
    echo '</table>';
    
}


if(isset($_GET['project_form'])){
    $mailText=serialize($_POST);
    $mailData = array(
    'to' => array('it@domkihot.ru'),
    'subject' => 'Из новой формы', 
    'message' => $mailText
    );
    flog($mailData,'mails'); 
    $send=decode(email_send($mailData));
    if(!$send['status']){ flog($mailData,'error'); echo 'Ошибка отправки но сообщение сохранено'; die(); }
    echo 'true';
}

?>