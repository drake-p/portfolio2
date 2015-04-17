<?php

chdir(dirname(__FILE__)); //set working directory to script's own directory

//set include path
$includePath = array();
$includePath[] = '.';
$includePath[] = '..';
$includePath[] = '../Zend';
$includePath[] = 'Zend';
$includePath[] = get_include_path();
$includePath = implode(PATH_SEPARATOR,$includePath);
set_include_path($includePath);

function handleError($errno, $errstr, $errfile, $errline, array $errcontext)
{
	if (0 == error_reporting()) {
		return false;
	}

	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

// Use Zend gdata framework to retrieve calendar data
function getCalFeed($end_date){

	// Suspected culprit for DST problems.  Hi, future self.  I really hope this worked.
	// date_default_timezone_set('EST');
	date_default_timezone_set('America/Detroit');

	require_once '../Zend/Loader.php';
 
	Zend_Loader::loadClass('Zend_Gdata');
	Zend_Loader::loadClass('Zend_Gdata_AuthSub');
	Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
	Zend_Loader::loadClass('Zend_Gdata_Calendar');

	$user = 'dummy796@gmail.com';
	$pass = 'f11235813'; 
	$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
 
	try
	{
		$client = Zend_Gdata_ClientLogin::getHttpClient($user,$pass,$service);	
	}

	catch(Exception $e)
	{
		// prevent Google username and password from being displayed
		// if a problem occurs
		echo "Could not connect to calendar.";
		die();
	}

	// parameters
	$calendar_user = 'k9r441id2i5g6g1gd62k6ocgi8%40group.calendar.google.com';
	$calendar_visibility = 'private-7b98426b0daa033d87e9582104c41f0e';
 
	$start_date = date('Y-n-j');
 
	// build query
	$gdataCal = new Zend_Gdata_Calendar($client);
 
	$query = $gdataCal->newEventQuery();
 
	$query->setUser($calendar_user);
	$query->setVisibility($calendar_visibility);			
 
	$query->setSingleEvents(true);
	$query->setProjection('full');
	$query->setOrderby('starttime');
	$query->setSortOrder('ascending');
	$query->setMaxResults(100);
	$query->setStartMin(strtotime($start_date));
	$query->setStartMax(strtotime($end_date));
 
	// execute and get results
	$feed = $gdataCal->getCalendarEventFeed($query);
	
	return $feed;
}

function formatWeek($feed) {

	$event_list = '';
	
	$last = intval(date('z'))-1;
	
	foreach ($feed as $event) {
	  // this is a bad hack!  I don't know why these times are 1 hour off!
	  //$start = strtotime("+1 hour", strtotime($event->when[0]->startTime));
		
		$start = strtotime($event->when[0]->startTime);

		// day - only prints once for multiple events (ex: Wednesday, December 3)
		if ($last <> date('z', $start)) {
			$event_list .= '<h6>' . date('l, F j', $start) . '</h6>';
		}
		$last = intval(date('z', $start));
		
		// time (ex: 8:45pm)
		$event_list .= '<dt>' . date('g:i a', $start) . '</dt>';
		
		// title
		$event_list .= '<dd>' . $event->title . '</dd><br/>';
	}
	
	return $event_list;
}

function formatDay($feed) {

	$event_list = '';
	
	// day (ex: 12/3/2012)
	$event_list .= '<em>' . date('n/j/Y') . '</em><p>';
	
	foreach ($feed as $event) {
	  // this is a bad hack!  I don't know why these times are 1 hour off!  
		$start = strtotime($event->when[0]->startTime);
		
		// only include today's events
		if (intval(date('z')) <> intval(date('z', $start))) {
			break;
		}
		
		// time (ex: 8:45pm)
		$event_list .= '<b>' . date('g:ia', $start) . '</b> ';
		
		// title
		$event_list .= $event->title . '<br/>';
	}
	$event_list .= '</p><br/>';
	
	return $event_list;
}

function writeCache($filename, $content) {
	$fh = fopen($filename,"w") or die ("could not open " . $filename . " for writing");
	fwrite($fh,$content);
	fclose($fh);
}

// error messages
$weekCache ="&lt;Unable to load calendar&gt;";
$dayCache = "&lt;Unable to load calendar&gt;";

// cache locations
$weekEventFile = "calendar-week.txt";
$dayEventFile = "calendar-day.txt";

// needed to supress warning messages
 set_error_handler('handleError');

// set end date for events
$range = date('Y-n-j', time() + (60*60 *24*7));

try {
	$event_feed = getCalFeed($range);
	
	$weekCache = formatWeek($event_feed);
	writeCache($weekEventFile, $weekCache);
	
	$dayCache = formatDay($event_feed);
	writeCache($dayEventFile, $dayCache);
}
catch (ErrorException $e) {
	writeCache("calendar-error.txt", $e . "\n");
}

restore_error_handler();

?>