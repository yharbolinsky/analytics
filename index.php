<?php
header('Content-Type: text/html; charset=utf-8', true);

require_once 'library/src/Google/autoload.php'; // or wherever autoload.php is located
require_once 'vendor/autoload.php';
require_once 'vendor/mandrill/mandrill/src/Mandrill.php';

session_start();

$client_id = '437878856334-61sc9t1hv8434cl0bg883bkj6pl3s3ao.apps.googleusercontent.com';
$client_secret = 'ezkYQfnU1Q9qm-7p6tBaRJBZ';
$redirect_uri = 'http://localhost/GOOGLE_API/oauth2callback.php';

//$mandrill = new Mandrill('ASItCLvTJDtrHlKQAQrSrw');

$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
//$client->setAuthConfigFile('client_secret.json');
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
    if ($_POST && $_POST['page_path']) {
        $analytics = new Google_Service_Analytics($client);
//        $profile = getFirstProfileId($analytics);
        $profile = getRIVSProfileId($analytics);

        $cresult = getResults($analytics, $profile);
        $crows = $cresult->getRows();
        $rows = $crows;
        $i = 1;
        $countDays = $_POST['days'];
//        echo '<pre>';
//        echo print_r($cresult);exit;

        while (count($crows)==10000){
            $cresult = getResults($analytics, $profile, $i * 10000);
            $crows = $cresult->getRows();
            $rows = array_merge($rows, $crows);
            $i++;
        }

        printResults($rows, $countDays);
    }
} else {
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

?>
<style>
    td {width: 100px;}
</style>

<form action="#" method="post" target="_blank">

    <label>Set number of days:</label>
    <input type="text" name="days" id="days" /><br /><br />

    <label>Set regex pages path:</label>
    <input type="text" name="page_path" id="page_path" /><br /><br />

    <input type="submit" value="Get result" />
</form>
<?php



function getRIVSProfileId(&$analytics) {
    $accounts = $analytics->management_accounts->listManagementAccounts();

    if (count($accounts->getItems()) > 0) {

        $items = $accounts->getItems();

        foreach($items as $item) {

            if ($item->name == 'RIVS') {

                $accountId = $item->getId();

            }

        }

        $properties = $analytics->management_webproperties->listManagementWebproperties($accountId);

        if (count($properties->getItems()) > 0) {
            $items = $properties->getItems();
            $firstPropertyId = $items[0]->getId();

            $profiles = $analytics->management_profiles->listManagementProfiles($accountId, $firstPropertyId);

            if (count($profiles->getItems()) > 0) {
                $items = $profiles->getItems();

                return $items[0]->getId();
            } else {
                throw new Exception('No views (profiles) found for this user.');
            }
        } else {
            throw new Exception('No properties found for this user.');
        }
    } else {
        throw new Exception('No accounts found for this user.');
    }
}


function getFirstProfileId(&$analytics) {
    $accounts = $analytics->management_accounts->listManagementAccounts();

    if (count($accounts->getItems()) > 0) {
        $items = $accounts->getItems();
        $firstAccountId = $items[0]->getId();

        $properties = $analytics->management_webproperties->listManagementWebproperties($firstAccountId);

        if (count($properties->getItems()) > 0) {
            $items = $properties->getItems();
            $firstPropertyId = $items[0]->getId();

            $profiles = $analytics->management_profiles->listManagementProfiles($firstAccountId, $firstPropertyId);

            if (count($profiles->getItems()) > 0) {
                $items = $profiles->getItems();

                return $items[0]->getId();
            } else {
                throw new Exception('No views (profiles) found for this user.');
            }
        } else {
            throw new Exception('No properties found for this user.');
        }
    } else {
        throw new Exception('No accounts found for this user.');
    }
}


function getResults(&$analytics, $profileId, $index = 1)  {

//    $from = date('Y-m-d', strtotime('-10 days')); // last 2 days
    $from = '2007-01-01'; // last 2 days
    $to = date('Y-m-d'); // today

    $metrics = 'ga:date';
    $path=$_POST['page_path'];
//    $metrics = 'ga:sessions, ga:visits, ga:visitors, ga:pageviews, ga:searchResultViews, ga:searchUniques';

//    $data = $analytics->data_ga->get(
//        'ga:' . $profileId,
//        $from,
//        $to,
//        $metrics,
//        array(
//            'filters' => 'ga:pagePath=' . $path,
//            'dimensions' => 'ga:pagePath',
//            'metrics' => 'ga:sessions, ga:visits, ga:visitors, ga:pageviews, ga:uniquePageviews,ga:newVisits,ga:timeOnPage',
//            'sort' => '-ga:pageviews',
//            'max-results' => '25'
//    ));


//    $data = $analytics->data_ga->get(
//        'ga:' . $profileId,
//        $from,
//        $to,
//        $metrics,
//        array(
//            'filters' => 'ga:pagePath=' . $path,
//            'dimensions' => 'ga:pagePath, ga:date',
//            'metrics' => 'ga:visitors, ga:pageviews, ga:uniquePageviews,ga:timeOnPage',
//            'sort' => '-ga:pagePath',
//            'max-results' => '25'
//        ));



    $data = $analytics->data_ga->get(
        'ga:' . $profileId,
        $from,
        $to,
        $metrics,
        array(
            'filters' => 'ga:pagePath=' . $path,
            'dimensions' => 'ga:pagePath, ga:date',
            'metrics' => 'ga:uniquePageviews',
            'sort' => '-ga:pagePath',
            'max-results' => '10000',
            'start-index' => $index
        ));

    return $data;

}


function printResults(&$rows, $countDays) {
    if (count($rows) > 0) {

//        $profileName = $results->getProfileInfo()->getProfileName();
//        $profileInfo = $results->getProfileInfo();

//        $rows = $results->getRows();
//        $rows = $results->getResults();

//        echo date("Y-m-d", strtotime($rows[0][1]));
//
//        echo '<pre>';
//var_dump($rows);exit;

        $title = '';
        $result = '';
        $header = 'Path;';
        $max = 0;
        $c = 0;
        $prevDays = '';
        $limitReached = false;

        foreach ($rows as $r) {
            if ($title != $r[0]) {
                $c = 1;

                $result .= "\n".$r[0].",".$r[2].",";
//                $c++;
                $title = $r[0];
                $limitReached = false;

                $prevDays = strtotime($r[1]) / 86400;

            } else if (!$limitReached) {

                $currentDay = strtotime($r[1]) / 86400;

                for ($i=$prevDays; $i < $currentDay - 1; $i++) {
                    $result .= "0,";
                    if($max < $c){
                        $max = $c;
                    }
                    $c++;
                    if ($c > $countDays) {
                        $limitReached = true;
                        continue 2;
                    }
                }

                if($max < $c){
                    $max = $c;
                }
//                $c = 0;
                $result .= $r[2].",";
                $c++;
                if ($c > $countDays) {
                    $limitReached = true;
                    continue;
                }

            }
        }

        $arr = [];

        foreach (range(0, $max) as $number) {
            $arr[] = 'Day #' . $number;
        }


        $arr2 = implode(',', $arr);
        $header .= $arr2 . ',';

        try {

            $mandrill = new Mandrill('ASItCLvTJDtrHlKQAQrSrw');

            $message = array(
                'html' => '<p>Analytics</p>',
                'text' => 'Test Analytics send result',
                'subject' => 'Analytics result',
                'from_email' => 'analytics@example.com',
                'from_name' => 'Analytics',
                'to' => array(
                    array(
                        'email' => 'yaroslav@m.artistsoft.coms',
                        'name' => 'Yaroslav',
                        'type' => 'to'
                    )
                )
            ,
                'attachments' => array(
                    array(
                        'type' => 'text/attachments',
                        'name' => 'analytics.txt',
                        'content' => $header.$result
                    )
                ),
            );
//            echo '<pre>';
//            var_dump($message);exit;
            $async = false;
            $ip_pool = 'Main Pool';
            $send_at = date('Y-m-d H:i:s');
            $result = $mandrill->messages->send($message, $async, $ip_pool, $send_at);
            print_r($result);

        } catch(Mandrill_Error $e) {
            echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            throw $e;
        }

//        header('Content-Type: text/x-csv');
//        header('Content-Disposition: attachment; filename=analytics.csv');
//        echo $header.$result;
        exit;


        print "<p>First view (profile) found: $profileName</p>";

        echo '<table><tr><td>Page path</td><td>Date</td><td>Visitors</td><td>Page views</td><td>Unique page views</td><td>Time on page</td></tr>';

        $counter = 0;

        foreach ($rows as $row) {

            echo '<tr>';
            echo '<td>' . $row[0] . '</td>';
            echo '<td>Day #' . $row[1] . '</td>';
            echo '<td>' . $row[2] . '</td>';
            echo '<td>' . $row[3] . '</td>';
            echo '<td>' . $row[4] . '</td>';
            echo '<td>' . $row[5] . '</td>';
            echo '</tr>';

            $counter++;
        }

        echo '</table>';

        echo '<hr />';

//        echo 'Result rows: <pre>';
//        print_r($rows);
        echo '<h4>ProfileInfo: </h4><pre>';
        print_r($profileInfo);
    } else {
        print "<p>No results found.</p>";
        exit;
    }
}
?>
