<?php
/**
  * maritime-electric-dump.php
  *
  * A PHP script to retrieve transaction history data from Maritime Electric's
  * customer portal (Maritime Electric is the electricity provider in Prince
  * Edward Island, Canada).
  *
  * Given a customer's web portal username and password (otherwise used to
  * login at https://secure.maritimeelectric.com/) this script logs in, 
  * and for each service address associated with the account retrieves
  * all available transaction data and dumps it into a file named
  * [account-number].csv.
  *   
  * Requirements: 
  *  - PHP (http://www.php.net) - on a Mac you have this; on a Linux host you probably do.
  *  - cURL (http://curl.haxx.se/) - you likely already have this.
  *  - PHP Simple HTML DOM Parser (http://simplehtmldom.sourceforge.net/index.htm)
  *
  * This program is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or (at
  * your option) any later version.
  *
  * This program is distributed in the hope that it will be useful, but
  * WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
  * General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
  * USA
  *
  * @version 0.1, May 4, 2011
  * @link https://github.com/reinvented/pei-energy/maritimeelectric
  * @author Peter Rukavina <peter@rukavina.net>
  * @copyright Copyright &copy; 2011, Reinvented Inc.
  * @license http://www.fsf.org/licensing/licenses/gpl.txt GNU Public License
  */

/**
  * ------------------------------------------------------------------------
  * Start of User-configurable options
  * ------------------------------------------------------------------------
  */

/**
  * These are the same values you use to login to the Maritime Electric
  * Customer Portal at https://secure.maritimeelectric.com/
  *
  * If you haven't already signed up for online access to your account, 
  * you will need your Customer Number, Premise Number, Contract Number,
  * Energy Used (from last bill), and an email address; with these you
  * can sign up for an online account instantly.
  *
  * You can hard-code the values here, or pass them on the command line, like:
  *
  * php maritime-electric-dump.php username password
  */

if ($argv[1] and $argv[2]) {
  $maritimeelectric_username = $argv[1];
  $maritimeelectric_password = $argv[2];
}
else {
  $maritimeelectric_username = "";
  $maritimeelectric_password = "";
}

/**
  * Where to put the data files that are generated. Defaults to your
  * home directory. Change if you want them to end up elsewhere.
  */
$outputdir = realpath("~");

/**
  * Where to put temporary files and cookie files used by cURL.
  */
$tmpdir = "/tmp";

/**
  * Include the PHP Simple HTML DOM Parser - default is to look for this in the
  * current directory; change the location if you've installed it elsewhere.
  */
require_once './simple_html_dom.php';

/**
  * Set the default PHP time zone to 'America/Halifax'. As all Maritime Electric
  * customers are in Prince Edward Island, nobody should need to change this ;-)
  */
date_default_timezone_set('America/Halifax');

/**
  * ------------------------------------------------------------------------
  * End of User-configurable options
  * ------------------------------------------------------------------------
  */

/**
  * Create a file to hold the meta-data about what we're about to grab.
  */
$mp = fopen($outputdir . "/maritime-electric-accounts.csv","w");
fwrite($mp,"NAME,BALANCE,ADDRESS1,SERVICE ADDRESS1,SERVICE ADDRESS2,ADDRESS2,ADDRESS3,ADDRESS4,ACCOUNT NUMBER\n");

/**
  * Start the data retrieving voodoo.
  *
  * The basic idea here is that we grab the login page's HTML, pull some hidden
  * variables from it and then post back to the login page, passing those back
  * along with our username and password.
  */
$vars = array();
$vars['ctl00$ContentPlaceHolder1$Login1$UserName'] = $maritimeelectric_username;
$vars['ctl00$ContentPlaceHolder1$Login1$Password'] = $maritimeelectric_password;
$vars['ctl00$ContentPlaceHolder1$Login1$LoginButton'] = "Log In";

/**
  * Grab the login page.
  */
exec("curl -s -c $tmpdir/maritimeelectric-cookies.txt -b $tmpdir/maritimeelectric-cookies.txt -o $tmpdir/maritimeelectric-login.html -L https://secure.maritimeelectric.com/customer/portal/login.aspx");
$html = file_get_html("$tmpdir/maritimeelectric-login.html");
$vars = makeHiddenVars($html,$vars);
$varscurl = makePostVars($vars);

/**
  * HTTP POST back to the login page, passing the hidden variables we grabbed earlier
  * along with our username and password.
  */
exec("curl -s -c $tmpdir/maritimeelectric-cookies.txt -b $tmpdir/maritimeelectric-cookies.txt -L --header 'Content-Length: " . strlen($varscurl) . "' -X POST -d '$varscurl' -o $tmpdir/maritimeelectric-loggedin.html -L https://secure.maritimeelectric.com/customer/portal/login.aspx");
$accountnumber = 1;
$html = file_get_html("$tmpdir/maritimeelectric-loggedin.html");
$hiddenvars = makeHiddenVars($html,$vars);

/**
  * Take the resulting HTML and find the INPUT elements with a name like:
  * 
  * ctl00$ContentPlaceHolder1$dgSummary$GVSummaryRowID_0$imgbtnTrans
  *
  * There is one INPUT element for every service address, with the name attribute
  * incrementing by one for each element -- RowID_0, RowID_1, etc.
  */
foreach($html->find('input') as $e) {
  if (strpos($e->attr['name'],"imgbtnTrans")) {
    $vars = $hiddenvars;
    $vars[$e->attr['name'] . ".x"] = "5";
    $vars[$e->attr['name'] . ".y"] = "5";
    $varscurl = makePostVars($vars);
    
    /**
      * Grab the first screen of the transactions by simulating a click on the "$" icon, the
      * name of which we just grabbed. This will return only a single page of transactions.
      */
    exec("curl -s -c $tmpdir/maritimeelectric-cookies.txt -b $tmpdir/maritimeelectric-cookies.txt -L --header 'Content-Length: " . strlen($varscurl) . "' -X POST -d '$varscurl' -o $tmpdir/maritimeelectric-transactions-$accountnumber.html -L https://secure.maritimeelectric.com/customer/account/default.aspx");

    $vars = array();
    $innerhtml = file_get_html("$tmpdir/maritimeelectric-transactions-$accountnumber.html");

    /**
      * Just for fun, pull out the account information for this service address.
      */
    $account['name'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtName]',0)->plaintext;
    $account['balance'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtBalance]',0)->plaintext;    
    $account['address1'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtAddress1]',0)->plaintext;    
    $account['serviceaddress1'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtSvcAddress1]',0)->plaintext;    
    $account['serviceaddress2'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtSvcAddress2]',0)->plaintext;    
    $account['address2'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtAddress2]',0)->plaintext;    
    $account['address3'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtAddress3]',0)->plaintext;    
    $account['address4'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtAddress4]',0)->plaintext;    
    $account['number'] = $innerhtml->find('span[id=ctl00_ContentPlaceHolder1_txtAcctNum]',0)->plaintext;    

    /**
      * Output the account metadata to a file that we opened earlier.
      */
    fwrite($mp,implode(",",$account) . "\n");

    $innerhiddenvars = makeHiddenVars($innerhtml,$vars);
    $vars = $innerhiddenvars;
    $vars['ctl00$ContentPlaceHolder1$ddlMaxTrans'] = 'All';
    $vars['ctl00$ContentPlaceHolder1$btnRefreshTrans'] = 'More';
    $varscurl = makePostVars($vars);

    /**
      * Now repost, but this time passing the parameters needed to get ALL the transactions.
      * On first blush it looks like this returns data back to the very opening of the account;
      * mine goes back only 10 years, so I'm not certain exactly how far back before 2000
      * you'll get data for.
      */
    exec("curl -s -c $tmpdir/maritimeelectric-cookies.txt -b $tmpdir/maritimeelectric-cookies.txt -L --header 'Content-Length: " . strlen($varscurl) . "' -X POST -d '$varscurl' -o $tmpdir/maritimeelectric-transactions-all-$accountnumber.html -L https://secure.maritimeelectric.com/customer/account/Transactions.aspx");

    /**
      * Now that we have the HTML containing all the transactions, we'll pull out
      * the transactions, massage the data a little, and output the data to a CSV file.
      * Note that the data is output in the order it is presented by Maritime Electric 
      * which is not, for some reason, always chronological.
      */
    $data = array();
    $transhtml = file_get_html("$tmpdir/maritimeelectric-transactions-all-$accountnumber.html");
    foreach($transhtml->find('tr[bgcolor=Gainsboro],tr[bgcolor=#EEEEEE]') as $r) {
      $row = array();
      foreach($r->find('td') as $c) {
        $row[] = $c->plaintext;
      }
      $row[0] = trim($row[0]);
      $row[1] = stripDollars($row[1]);
      $row[2] = strtotime($row[2]);
      $row[2] = strftime("%Y-%m-%d",$row[2]);

      $data[] = $row;
    }
    $fp = fopen($outputdir . "/" . $account['number'] . ".csv","w");
    fwrite($fp,"TRANSACTION,AMOUNT,DATE\n");
    foreach($data as $key => $value) {
      fwrite($fp,implode(",",$value) . "\n");
    }
    fclose($fp);

    $accountnumber++;
  }
}

fclose($mp);

/**
  * Helper Functions
  */

/**
  * Given a string "$ 1.48", return "1.48"
  */
function stripDollars($target) {
  $target = str_replace("\$","",$target);
  $target = str_replace(" ","",$target);
  return $target;
}

/**
  * Take some HTML and use the PHP Simple HTML DOM Parser to pull out
  * all of the hidden variables and return them as an array.
  */
function makeHiddenVars($html,$vars) {
  foreach($html->find('input') as $e) {
    if ($e->attr['type'] == "hidden") {
      $vars[$e->attr['name']] = $e->attr['value'];
    }
  }
  return $vars;
}

/**
  * Take an array of parameters and return them as a string of parameters
  * suitable for passing to cURL as an HTTP POST.
  */
function makePostVars($vars) {
  $varsout = '';
  foreach($vars as $key => $value) {
    $varsout[] = $key . "=" . urlencode($value);
  }
  $varscurl = implode("&",$varsout);
  return $varscurl;
}  

