<?php
namespace tiolib;


  function updateCountToken($id_token){
        global $sqlPDO;



        $sqlPDO->queryex("UPDATE
                    tokens AS a
                INNER JOIN (
                        SELECT id_token, SUM(total_value) as total_value, SUM(total_count) as total_count
                        FROM (
                        SELECT
                            tokens_contents_rel.id_token,
                            SUM(tokens_contents_rel.`value`) AS total_value,
                            COUNT(*) AS total_count
                        FROM
                            tokens_contents_rel
                        WHERE tokens_contents_rel.id_token = :id_token
                        UNION
                        SELECT
                            tokens_media_rel.id_token,
                            SUM(tokens_media_rel.`value`) AS total_value,
                            COUNT(*) AS total_count
                        FROM
                            tokens_media_rel
                        WHERE tokens_media_rel.id_token = :id_token
                        ) as union_tokens_count

                ) AS b ON b.id_token = a.id
                SET a.total_count = b.total_count, a.total_value = b.total_value
                WHERE
                    a.id = :id_token", array("id_token" =>$id_token), false);

    }


function getTokenList($search, $full = 1){
       global $sqlPDO;

       $valid_words = getNltkWords($search);
       $searchQuery = '';
       $count = 0;
       $params = [];
       //echo count($valid_words);
       $min_count = floor(count($valid_words)/2);
       for($i=0; $i<count($valid_words); $i++){

            if(count($params)> 0){ $searchQuery .= ','; }
            $searchQuery .=  ":".count($params);
            $params[] = $valid_words[$i];

            if(!empty($valid_words[$i-1])){
                 if(count($params)> 0){ $searchQuery .= ','; }
                $searchQuery .=  ":".count($params);
                $params[] = $valid_words[$i-1]." ".$valid_words[$i];
            }

            if(!empty($valid_words[$i-1]) && !empty($valid_words[$i-2])){
                 if(count($params)> 0){ $searchQuery .= ','; }
                $searchQuery .=  ":".count($params);
                $params[] = $valid_words[$i-2]." ".$valid_words[$i-1]." ".$valid_words[$i];
            }
        }

        $json_return = [];


        $query = 'SELECT id, token, tag, radix, total_value, total_count FROM tokens
                  WHERE token IN ('.$searchQuery.')';

        if(!$full){
            $query .=" AND active = 1 ";
        }

        $rs = empty($searchQuery) ? array() : $sqlPDO->queryex($query, $params, true);
        //$sqlPDO->debug(true);
        $json_return["query"] = [];


        if(!empty($rs)){

            $json_return["query"]["tokens"] = $rs;
            $json_return["query"]["search"] = $search;
            $json_return["query"]["full"] = $full;
            $json_return["query"]["min_count"] = $min_count;
            $token_string_list = "";
            $params = [];

            foreach($rs as $token){
                if(count($params) > 0){ $token_string_list .= ','; }
                $token_string_list .= ":".count($params);
                $params[] = (int)$token["id"];
            }
            $json_return["token_string_list"] = $token_string_list;
            $json_return["params"] = $params;
        }

      return $json_return;
}



function media_search($search,  $id_domain, $start_index = 0, $num_items = 25, $full = 1, $order = '', $num_media = 1, $id_object_types = 1,  $log_search = true){
        global $sqlPDO;
        $json_return = [];
        $tokenList = getTokenList($search, $full);
        $json_return['query'] = $tokenList['query'];
        $json_return["query"]["start_index"] = $start_index;
        $json_return["query"]["num_items"] = $num_items;
        $json_return["query"]["total_results"] = "0";

        if(!empty($tokenList["query"]["tokens"])){
           $token_string_list = $tokenList["token_string_list"];
            $params = $tokenList['params'];
            $params["id_domain"] = $id_domain;
            $query = "SELECT COUNT(*) as total
                 FROM (
                        SELECT c.id FROM tokens_contents_rel as a
                        INNER JOIN view_fe_objects AS c ON a.id_content = c.id_contents
                        WHERE c.id_domains = :id_domain AND a.id_token IN ($token_string_list) ";


            $query .=" AND c.id_object_types = 1 ";

            if(!$full){
                $query .=" AND a.active = 1 AND c.active = 1 AND c.content_type < 100 ";
            }

            $query .="
                GROUP BY c.id
                    HAVING COUNT(a.`id_token`) > :min_count
                ) as b ";

            $params["min_count"] = $tokenList["query"]["min_count"];;

            $rs = $sqlPDO->queryex($query, $params, true);


            if(empty($tokenList["query"]["tokens"])){
                $json_return["query"]["total_results"] = "0";
            }else{
                $json_return["query"]["total_results"] = $rs[0]["total"];

                if(!empty($num_items)){
                   $params["start_index"] = (int)$start_index;
                   $params["num_items"] = (int)$num_items;
                }

                $query = "SELECT
                            c.*,
                            COUNT(a.`id_token`) AS total_count,
                            SUM(a.`value`) as total_value
                        FROM
                            view_fe_objects AS c
                            INNER JOIN tokens_contents_rel AS a ON a.id_content = c.id_contents
                        WHERE c.id_domains = :id_domain ";

                $query .="AND c.id_object_types = 1 ";

                if(!$full){
                    $query .="AND a.active = 1 AND c.active = 1  AND c.content_type < 100  ";
                }
                $query .= "AND c.date_pubblication < NOW()
                        AND a.id_token IN ($token_string_list)
                        GROUP BY
                            c.id
                        HAVING COUNT(a.`id_token`) > :min_count ";

                switch($order){
                    case 'time':
                        $query .= ' ORDER BY date_pubblication DESC ';
                        break;
                    default :
                        $query .= ' ORDER BY total_count DESC, total_value DESC, date_pubblication DESC ';
                }
                if(!empty($num_items)){
                    $query .=  " LIMIT :start_index, :num_items";
                }

                $params["min_count"] = $tokenList["query"]["min_count"];

                $rs = $sqlPDO->queryex($query, $params, true);

                $json_return["results"] = $rs;
            }

        }

    return json_encode($json_return);
}



function token_search($search, $id_domain, $start_index = 0, $num_items = 25, $full = 1, $order = '', $num_media = 1, $id_object_types = 1,  $log_search = true){
	    global $sqlPDO;
        $json_return = [];
        $tokenList = getTokenList($search, $full);
        $json_return['query'] = $tokenList['query'];
        $json_return["query"]["start_index"] = $start_index;
        $json_return["query"]["num_items"] = $num_items;
        $json_return["query"]["total_results"] = "0";

        if(!empty($tokenList["query"]["tokens"])){

            $token_string_list = $tokenList["token_string_list"];
            $params = $tokenList['params'];
            $params["id_domain"] = $id_domain;
            $query = "SELECT COUNT(*) as total
            	 FROM (
            	 		SELECT c.id FROM tokens_contents_rel as a
            	 		INNER JOIN view_fe_section_contents AS c ON a.id_content = c.id
            	 		WHERE c.id_domains = :id_domain AND a.id_token IN ($token_string_list) ";

            if(!$full){
	        	$query .=" AND a.active = 1 AND c.active = 1 AND c.content_type < 100 ";
	        }

	        $query .="
	        	GROUP BY c.id
			        HAVING COUNT(a.`id_token`) > :min_count
		        ) as b ";

			$params["min_count"] = $tokenList["query"]["min_count"];;

            $rs = $sqlPDO->queryex($query, $params, true);

            if(empty($tokenList["query"]["tokens"])){
            	$json_return["query"]["total_results"] = "0";
            }else{
            	$json_return["query"]["total_results"] = $rs[0]["total"];

	            $params["start_index"] = (int)$start_index;
	            $params["num_items"] = (int)$num_items;

	            $query = "SELECT
		                    c.*,
		                    COUNT(a.`id_token`) AS total_count,
		                    SUM(a.`value`) -FLOOR(DATEDIFF(NOW(), c.date_pubblication) / 30) AS total_value
		                FROM
		                    view_fe_section_contents AS c
		                	INNER JOIN tokens_contents_rel AS a ON a.id_content = c.id
		                WHERE c.id_domains = :id_domain AND";

		        if(!$full){
		        	$query .=" a.active = 1 AND c.active = 1  AND c.content_type < 100 AND ";
		        }

		        $query .= "  c.date_pubblication < NOW()
		                AND a.id_token IN ($token_string_list)
		                GROUP BY
		                    c.id
		                HAVING COUNT(a.`id_token`) > :min_count ";

				switch($order){
					case 'time':
						$query .= ' ORDER BY date_pubblication DESC ';
						break;
					default :
						$query .= ' ORDER BY total_count DESC, total_value DESC, date_pubblication DESC ';
				}

		            $query .=  " LIMIT :start_index, :num_items";

		        $params["min_count"] = $tokenList["query"]["min_count"];

	            $rs = $sqlPDO->queryex($query, $params, true);

                $media_params = [];
                if($num_media > -1){
    	            for ($i = 0; $i < sizeof($rs); $i++){
                            $media_params['id_content'] = $rs[$i]['id'];
                            $get_objects = 'SELECT id, id_object_types, src, param, description, file_path, file_name, file_version, object_types_path, object_text, sort
                                            FROM view_fe_objects
                                            WHERE id_contents = :id_content ';

                            if(!empty($id_object_types)){
                                $get_objects .= ' AND id_object_types = :id_object_types ';
                                $media_params['id_object_types'] = $id_object_types;
                            }

                            $get_objects .= ' ORDER BY sort ASC ';

                            if(!empty($num_media)){
                                $get_objects .= 'LIMIT :num_media ';
                                $media_params["num_media"] = (int)$num_media;
                            }

    	            		$rs[$i]['objects'] = $sqlPDO->queryex($get_objects, $media_params, true);
    	            }
                }

	            $json_return["results"] = $rs;

	        }
    	}


        if($log_search){
    	  $params = [];
    	  $params["hash"] =  md5($search.$full.$order);
    	  $params["search"] = $search;
    	  $params["results"] = $json_return["query"]["total_results"];
		  $params["full"] = $full;
		  $params["order"] = $order;
		  $params["id_domain"] = $id_domain;

    	   $sqlPDO->queryex("INSERT INTO tokens_search (hash, search, results, first, last, count, full, id_domains) VALUES (:hash, :search, :results, NOW(), NOW(), 1, :full, :id_domain) ON DUPLICATE KEY UPDATE results = :results, last = NOW(), count = count +1 ", $params);
        }

    	return json_encode($json_return);

	}


    function getSimilar($id_content, $type = 'article', $limit_value = 10, $limit_results = 10, $debug = false){
        global $sqlPDO, $this_app;
        $tags = [];
        $params = [];
        $params["id_content"] = $id_content;
        $possible_value = 1;

        if($debug){
           $time_start = microtime(true);
        }

        $result = [];

        $debug_string = '';
        $error_string = '';
        if($debug){
            $time_interval = microtime(true);
        }
        $date_pubb_rs = $sqlPDO->queryex("SELECT date_pubblication FROm cms_contents WHERE id = :id_content ", $params, true);

        if(empty($date_pubb_rs)){
            $out .='<div style="float: left; clear: both; padding: 10px;">Articolo non esiste</div>';
            return $out;
        }
        $date_pubb = $date_pubb_rs[0]["date_pubblication"];


        $params = [];
        $params["id_content"] = $id_content;
        $orig_tokens_query = $sqlPDO->queryex("SELECT
                        tokens.token, tokens_contents_rel.id_token, tokens_contents_rel.count, tokens_contents_rel.value
                    FROM
                        tokens_contents_rel INNER JOIN tokens ON tokens.id = tokens_contents_rel.id_token
                    WHERE
                        tokens.active = 1
                        AND    tokens_contents_rel.active = 1
                        AND tokens_contents_rel.id_content = :id_content
                    ORDER BY tokens_contents_rel.value desc, tokens_contents_rel.id_token asc
                    LIMIT 30 "
                        , $params, true);

        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);

            $debug_string .= $time.' seconds get orig_tokens<br />';
            $time_interval = microtime(true);
        }

        $token_search_string = "";

        $params = [];

        $id_token_strings = "";
        $id_token_strings2 = "";
        $possible_value = 0;
        $orig_count = count($orig_tokens_query);
        if(empty($orig_count)){
            $result['error'] ='<div style="float: left; clear: both; padding: 10px;">Nessun Articolo Trovato</div>';
            return $result;
        }
        foreach($orig_tokens_query as  $token){
            $id_token_strings .= (empty($params) ? "" : ",").":".count($params);
            $orig_tokens[$token["id_token"]] = [];
            $orig_tokens[$token["id_token"]]["token"]  = $token["token"];
            $orig_tokens[$token["id_token"]]["value"]  = $token["value"];
            $params[] = $token["id_token"];
            $possible_value += $token["value"];

        }

        $params["id_content"] = $id_content;
        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);
            $debug_string .= $time.' generate query<br />';
            $time_interval = microtime(true);
        }

        $rs  = $sqlPDO->queryex("
                    SELECT
                        id_token, id_content, value
                    FROM
                        tokens_contents_rel
                    WHERE
                     id_token IN ($id_token_strings)
                    AND id_content != :id_content
                    AND active = 1", $params, true, false);

        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);
            $debug_string .= $time.' execute query<br />';
            $time_interval = microtime(true);
        }

        $db_similar = [];

        $average = 0;

        while($token = $rs->fetch()){
           if(empty($db_similar[$token["id_content"]])){
                $db_similar[$token["id_content"]] = [];
                $db_similar[$token["id_content"]]["value"] = 0;
                $db_similar[$token["id_content"]]["count"] = 0;
            }

            $db_similar[$token["id_content"]]["value"] = $db_similar[$token["id_content"]]["value"] + ($token["value"] * $orig_tokens[$token["id_token"]]["value"]);
            $average  = $average + $db_similar[$token["id_content"]]["value"];
            $db_similar[$token["id_content"]]["count"] += 1;
         }
         $similar = [];

        if($debug){
            $debug_string .=count($db_similar)." db results<br />";
        }

         foreach($db_similar as $key => $token){

            if($token["value"] > 10 && $token['count'] > 2){
                $token["id_content"] = $key;
                $similar[] = $token;
            }
         }

        unset($db_similar);
        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);

            $debug_string .= $time.' seconds get tokens (programmatic)<br />';
            $time_interval = microtime(true);
            $debug_string .=count($similar)." filter value<br />";
        }

        usort($similar,  function ($a, $b){
            if($a["value"] == $b["value"]){
                return 0;
            }

            return $a["value"] > $b["value"] ? -1 : 1;

        });

        if($debug){

            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);

            $debug_string .= $time.' sort and filter tokens <br />';
            $time_interval = microtime(true);
        }
        //pre($similar);

        $cut = 150; //default

        $similar = array_slice($similar, 0, $cut);
        //pre($similar);
        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);
            $debug_string.= $time.' cut tokens programmatic<br />';
            $time_interval = microtime(true);
        }

        foreach($similar as  $key => $token){
               $similar[$key]["perc"] = floor(($token['value']/$possible_value)*100);
        }

        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);
            $debug_string.= $time.' calc perc<br />';
            $time_interval = microtime(true);
        }

        $similar = array_filter($similar, function ($var){
            if($var["perc"] > 15){
                return true;
            }
            return false;
        });
        if($debug){
            $debug_string.=count($similar)." filterSimval<br />";
        }
        $median_value = ($possible_value/$orig_count);

        $params = [];
        $id_content_strings = "";
        if(empty($similar)){
            $result['error'] ='<div style="float: left; clear: both; padding: 10px;">Nessun Articolo Trovato</div>';
            return $result;
        }
        foreach($similar as  $key => $token){
            $id_content_strings .= (empty($params) ? "" : ",").":".count($params);
            $params[] = $token["id_content"];
        }

        $d = $this_app->get_session('working_domain');
        $params["date_article"] = $date_pubb;
        $params["id_domains"] = $d['id'];

        if($type == "article"){

        $articleSQL = $sqlPDO->queryex("SELECT
                                           view_fe_section_contents.*,
                                           FLOOR(DATEDIFF(:date_article, date_pubblication) / 30) as month_difference,
                                           active_count
                                        FROM
                                           view_fe_section_contents
                                           INNER JOIN tokens_contents_rel_count ON tokens_contents_rel_count.id_content = view_fe_section_contents.id
                                        WHERE
                                            active = 1
                                            AND id_domains = :id_domains
                                            AND date_pubblication < NOW()
                                            AND  id IN ($id_content_strings)", $params, true);

        foreach($articleSQL as  $article){

                $key = array_search($article["id"], array_column($similar, 'id_content'));

                $time_interval2 = microtime(true);

                if(!empty($article["active_count"])){
                    $perc_calc_2 = floor((($similar[$key]['value']/$possible_value) * ($similar[$key]["count"]/$article["active_count"]))*100);
                    $perc_calc_3 = floor((($similar[$key]['value']/$article["active_count"]) / $median_value )*100);
                    $similar[$key]["perc_calc_2"] = $perc_calc_2;
                    $similar[$key]["perc_calc_3"] = $perc_calc_3;

                    if($perc_calc_2 > 4 && $perc_calc_3 > 10){  //elimino valori non interessanti
                        $combined_perc = floor(($similar[$key]["perc"]  + $perc_calc_2 + $perc_calc_3) / 3) - abs($article['month_difference']);
                        $similar[$key]["perc"] = $combined_perc;
                        $similar[$key]["article"] = $article;
                    }else{
                        $similar[$key]["perc"] = 0;
                    }
                }else{
                    $similar[$key]["perc"] = 0;
                }
            }
        }

        if($type == "images" || $type = "videos"){
            $article_similar = $similar;
            $similar = [];
            if($type == "images"){
                $params['id_object_types'] = 1;
            }
            if($type == "videos"){
                $params['id_object_types'] = 7;
            }
            $objectSql = $sqlPDO->queryex("SELECT
                                               view_fe_objects.*,
                                               FLOOR(DATEDIFF(:date_article, date_pubblication) / 30) as month_difference,
                                               active_count
                                            FROM
                                               view_fe_objects
                                               INNER JOIN tokens_contents_rel_count ON tokens_contents_rel_count.id_content = view_fe_objects.id_contents
                                            WHERE
                                                view_fe_objects.active = 1
                                                AND view_fe_objects.id_domains = :id_domains
                                                AND view_fe_objects.id_object_types = :id_object_types
                                                AND view_fe_objects.date_pubblication < NOW()
                                                AND  view_fe_objects.id_contents IN ($id_content_strings)", $params, true);

            foreach($objectSql as  $object){

                    $article_key = array_search($object["id_contents"], array_column($article_similar, 'id_content'));

                    $similar[] = array(
                        'id_object' => $object["id"],
                        'id_content' => $object["id_contents"],
                        'value' => $article_similar[$article_key]['value'],
                        'count' => $article_similar[$article_key]['value'],
                        'perc' => $article_similar[$article_key]['value'],
                    );

                    $key = array_search($object["id"], array_column($similar, 'id_object'));

                    $time_interval2 = microtime(true);

                    if(!empty($object["active_count"])){
                        $perc_calc_2 = floor((($similar[$key]['value']/$possible_value) * ($similar[$key]["count"]/$object["active_count"]))*100);
                        $perc_calc_3 = floor((($similar[$key]['value']/$object["active_count"]) / $median_value )*100);
                        $similar[$key]["perc_calc_2"] = $perc_calc_2;
                        $similar[$key]["perc_calc_3"] = $perc_calc_3;

                        if($perc_calc_2 > 4 && $perc_calc_3 > 10){  //elimino valori non interessanti
                            $combined_perc = floor(($similar[$key]["perc"]  + $perc_calc_2 + $perc_calc_3) / 3) - abs($object['month_difference']);
                            if($object['sort'] === '0'){
                                $similar[$key]["perc"] = $combined_perc + 1;
                            }else{
                                $similar[$key]["perc"] = $combined_perc  -  floor($object['sort']/10);
                            }
                            $similar[$key]["object"] = $object;
                        }else{
                            $similar[$key]["perc"] = 0;
                        }
                    }else{
                        $similar[$key]["perc"] = 0;
                    }
                }
        }

        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);
            $debug_string .=' in '. $time.' seconds (singlequery)<br />';
            $time_interval = microtime(true);
        }

        $similar = array_filter($similar, function ($var) use ($limit_value){
            if($var["perc"] > $limit_value){
                return true;
            }
            return false;
        });
        if($debug){
            $debug_string .=count($similar)." filterLimit<br />";
        }

        usort($similar,  function ($a, $b){
            if($a["perc"] == $b["perc"]){
                return 0;
            }

            return $a["perc"] > $b["perc"] ? -1 : 1;

        });

        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_interval, 4);
            $debug_string .= $time.' sort and filter articles<br />';
            $time_interval = microtime(true);
            $debug_string .= $orig_count."<br />";
            $debug_string .=count($similar)." programmatic<br />";
        }

        if($debug){
            $time_end = microtime(true);
            $time = round($time_end - $time_start, 4);
            $debug_string .= $time.' seconds complete';
        }
        $result['debug'] = $debug_string;
        $result['similar'] = $similar;

        return $result;
    }

?>
