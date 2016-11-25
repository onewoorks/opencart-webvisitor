<?php

class ModelModuleVisitor extends Model {

	public function addVisitor() {

       $ip = determineIP();

       $table = DB_PREFIX . "visitor";
       $datetime = date("YmdHis", strtotime('+15 hours'));
       $tm = time();
       $online_limit = time() - 1800;
       //$geo_api = 'http://api.db-ip.com/addrinfo?addr='.$ip.'&api_key=6c285887f703bac6ac1c365760af89ee7d2c11c5';
       //$geo_api = 'http://api.hostip.info/get_json.php?ip='.$ip;
       $geo_api = 'http://ip-api.com/json/'.$ip; 
       $geo_json = @file_get_contents($geo_api);
       $c = json_decode($geo_json);
       //$country = $c->country_code;
       $country = str_replace("'","\'",$c->countryCode);
       
       $query = $this->db->query(
         "INSERT INTO $table (id,ip, datetime, hits, online,country,geolocation) VALUES('','$ip','$datetime','1','$tm','$country','$geo_json') ON DUPLICATE KEY " .
         "UPDATE hits=hits+1, online='$tm';"
       );

       $res = array();

       $res["today_visitor"]  = $this->db->query("SELECT * FROM $table WHERE datetime='$datetime' GROUP BY ip")->num_rows;
       $res["total_visitor"]  = $this->db->query("SELECT COUNT(hits) FROM $table")->row["COUNT(hits)"];
       $res["today_hits"]     = $this->db->query("SELECT SUM(hits) FROM $table WHERE datetime='$datetime' GROUP BY datetime")->row["SUM(hits)"];
       $res["total_hits"]     = $this->db->query("SELECT SUM(hits) FROM $table")->row["SUM(hits)"];
       $res["online_visitor"] = $this->db->query("SELECT * FROM $table WHERE online > '$online_limit'")->num_rows;

       return $res;
	}



}


/* By Grant Burton @ BURTONTECH.COM (11-30-2008): IP-Proxy-Cluster Fix */
function checkIP($ip) {
   if (!empty($ip) && ip2long($ip)!=-1 && ip2long($ip)!=false) {
       $private_ips = array (
       array('0.0.0.0','2.255.255.255'),
       array('10.0.0.0','10.255.255.255'),
       array('127.0.0.0','127.255.255.255'),
       array('169.254.0.0','169.254.255.255'),
       array('172.16.0.0','172.31.255.255'),
       array('192.0.2.0','192.0.2.255'),
       array('192.168.0.0','192.168.255.255'),
       array('255.255.255.0','255.255.255.255')
       );

       foreach ($private_ips as $r) {
           $min = ip2long($r[0]);
           $max = ip2long($r[1]);
           if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
       }
       return true;
   } else {
       return false;
   }
}

function determineIP() {

   if (isset($_SERVER["HTTP_CLIENT_IP"])) {
     if (checkIP($_SERVER["HTTP_CLIENT_IP"])) {
       return $_SERVER["HTTP_CLIENT_IP"];
     }
   }

   if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
     foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
         if (checkIP(trim($ip))) {
             return $ip;
         }
     }
   }

   if (isset($_SERVER["HTTP_X_FORWARDED"])) {
     if (checkIP($_SERVER["HTTP_X_FORWARDED"])) {
       return $_SERVER["HTTP_X_FORWARDED"];
     }
   }

   if (isset($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])) {
     if (checkIP($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])) {
       return $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
     }
   }

   if (isset($_SERVER["HTTP_FORWARDED_FOR"])) {
     if (checkIP($_SERVER["HTTP_FORWARDED_FOR"])) {
       return $_SERVER["HTTP_FORWARDED_FOR"];
     }
   }

   if (isset($_SERVER["HTTP_FORWARDED"])) {
     if (checkIP($_SERVER["HTTP_FORWARDED"])) {
       return $_SERVER["HTTP_FORWARDED"];
     }
   }

   return $_SERVER["REMOTE_ADDR"];
}