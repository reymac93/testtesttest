3c3
< define( 'I_DEBUG', FALSE );
---
> define( 'I_DEBUG', TRUE );
8c8
< //	echo "<pre>$s</pre>";
---
> 	echo "<pre>$s</pre>";
134a135,136
> $qry = "SELECT LAST_INSERT_ID()";
> if (I_DEBUG) pre_echo( $qry );
140a143,144
> $qry = "SELECT LAST_INSERT_ID()";
> if (I_DEBUG) pre_echo( $qry );
156a161,162
> $qry = "SELECT LAST_INSERT_ID()";
> if (I_DEBUG) pre_echo( $qry );
162a169,170
> $qry = "SELECT LAST_INSERT_ID()";
> if (I_DEBUG) pre_echo( $qry );
180c188
< // table into the production header table.  If successful, not the
---
> // table into the production header table.  If successful, note the
190c198
< if (I_DEBUG) message_log_append( $msg_log, " begin do_insert" );
---
> if (I_DEBUG) pre_echo( " begin do_insert" );
197c205
< //pre_echo( $qry );
---
> if (I_DEBUG) pre_echo( $qry );
206c214
< //pre_echo( $qry );
---
> if (I_DEBUG) pre_echo( $qry );
216c224
< //pre_echo( $qry );
---
> if (I_DEBUG) pre_echo( $qry );
226c234
< //pre_echo( $qry );
---
> if (I_DEBUG) pre_echo( $qry );
234a243,244
> $qry = "SHOW COLUMNS FROM temp_header";
> if (I_DEBUG) pre_echo( $qry );
242a253,254
> $qry = "SELECT LAST_INSERT_ID()";
> if (I_DEBUG) pre_echo( $qry );
250c262
< //if (I_DEBUG) message_log_append( $msg_log, "do_insert: old_seq = $old_seq" );
---
> //if (I_DEBUG) pre_echo( "do_insert: old_seq = $old_seq" );
253c265
< //pre_echo( $qry );
---
> if (I_DEBUG) pre_echo( $qry );
260a273,274
> $qry = "SELECT LAST_INSERT_ID()";
> if (I_DEBUG) pre_echo( $qry );
264c278
< 		else message_log_append( $msg_log, "temp detail insert failed: " . 
---
> 		else message_log_append( $msg_log, "Seq unchanged after header insert: " . 
270c284
< if (I_DEBUG) message_log_append( $msg_log, "do_insert: new contract_header seq = $seq" );
---
> //if (I_DEBUG) pre_echo( "do_insert: new contract_header seq = $seq" );
272c286
< //pre_echo( $qry );
---
> if (I_DEBUG) pre_echo( $qry );
278a293,294
> $qry = "SHOW COLUMNS FROM temp_detail";
> if (I_DEBUG) pre_echo( $qry );
285a302,303
> $qry = "SELECT LAST_INSERT_ID()";
> if (I_DEBUG) pre_echo( $qry );
287c305
< //if (I_DEBUG) message_log_append( $msg_log, "detail: old_seq = $old_seq" );
---
> //if (I_DEBUG) pre_echo( "detail: old_seq = $old_seq" );
297,298c315
< if (I_DEBUG) message_log_append( $msg_log, "detail: $qry" );
< //pre_echo( $qry );
---
> if (I_DEBUG) pre_echo( "detail: $qry" );
304a322,323
> $qry = "SELECT LAST_INSERT_ID()";
> if (I_DEBUG) pre_echo( $qry );
308d326
< if (I_DEBUG) message_log_append( $msg_log, "do_insert: success" );
312a331
> if (I_DEBUG) pre_echo( "do_insert: success = " . ($success ? 'TRUE' : 'FALSE') );
318,321c337
< function insert_sql_data( $db_conn, 
< 			  $p_campaigns,
< 			  $p_headers,
< 			  $p_details )
---
> function insert_table_prep( $p_db_conn, $p_msg_log )
323,325c339,341
< // insert the header and detail data into the file
< // with appropriate safeguards to ensure full 
< // completion or rollback of the transaction.
---
> // do all the table CREATEs and ALTERs that need to be done
> // before we can actually start inserting data.  return TRUE
> // iff all goes well, else FALSE.
328,333d343
< GLOBAL $msg_log;
< 
< 	$success = TRUE;
< 
< // Wrap all this in a transaction
< 
336,337c346,347
< //pre_echo( $qry );
< 	$success = mysql_query( $qry, $db_conn );
---
> if (I_DEBUG) pre_echo( $qry );
> 	$success = mysql_query( $qry, $p_db_conn );
344,348c354,358
< 		$qry = "CREATE TEMPORARY TABLE temp_header LIKE contract_header";
< //pre_echo( $qry );
< 		$success = mysql_query( $qry, $db_conn );
< 		if (!$success) 
< 			message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
---
> 	    $qry = "CREATE TEMPORARY TABLE temp_header LIKE contract_header";
> if (I_DEBUG) pre_echo( $qry );
> 	    $success = mysql_query( $qry, $p_db_conn );
> 	    if (!$success) 
> 		message_log_append( $p_msg_log, mysql_error( $p_db_conn ), MSG_LOG_ERROR );
352,356c362,366
< 		$qry = "CREATE TEMPORARY TABLE temp_detail LIKE contract_detail";
< //pre_echo( $qry );
< 		$success = mysql_query( $qry, $db_conn );
< 		if (!$success)
< 			message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
---
> 	    $qry = "CREATE TEMPORARY TABLE temp_detail LIKE contract_detail";
> if (I_DEBUG) pre_echo( $qry );
> 	    $success = mysql_query( $qry, $p_db_conn );
> 	    if (!$success)
> 		message_log_append( $p_msg_log, mysql_error( $p_db_conn ), MSG_LOG_ERROR );
360,364c370,374
< 		$qry = "ALTER TABLE temp_header DROP COLUMN Seq";
< //pre_echo( $qry );
< 		$success = mysql_query( $qry, $db_conn );
< 		if (!$success)
< 			message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
---
> 	    $qry = "ALTER TABLE temp_header DROP COLUMN Seq";
> if (I_DEBUG) pre_echo( $qry );
> 	    $success = mysql_query( $qry, $p_db_conn );
> 	    if (!$success)
> 		message_log_append( $p_msg_log, mysql_error( $p_db_conn ), MSG_LOG_ERROR );
368,372c378,382
< 		$qry = "ALTER TABLE temp_detail DROP COLUMN Line";
< //pre_echo( $qry );
< 		$success = mysql_query( $qry, $db_conn );
< 		if (!$success)
< 			message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
---
> 	    $qry = "ALTER TABLE temp_detail DROP COLUMN Line";
> if (I_DEBUG) pre_echo( $qry );
> 	    $success = mysql_query( $qry, $p_db_conn );
> 	    if (!$success)
> 		message_log_append( $p_msg_log, mysql_error( $p_db_conn ), MSG_LOG_ERROR );
374a385,404
> 	return( $success );
> 
> } // insert_table_prep
> 
> 
> function insert_sql_data( $db_conn, 
> 			  $p_campaigns,
> 			  $p_headers,
> 			  $p_details )
> 
> // insert the header and detail data into the file
> // with appropriate safeguards to ensure full 
> // completion or rollback of the transaction.
> 
> {
> GLOBAL $msg_log;
> 
> // first set up all the tables we'll need for our insert transaction
> 	$success = insert_table_prep( $db_conn, $msg_log );
> 
378a409,410
> // Wrap all this in a transaction:
> 
380,382c412,414
< //echo "<pre>";
< //var_dump( $p_campaigns );  echo "</pre><br>";
< 		$success = begin( $db_conn );
---
> $qry = "START TRANSACTION";
> if (I_DEBUG) pre_echo( $qry );
> 		$success = begin( $db_conn ); // begin transaction
388,389d419
< // do the work here, and keep track of $success
< 
454a485,487
> pre_echo( "insert_sql_data: key $key, syscode " . $p_headers[$key]['SysCode'] . 
> 	", detail count " . count( $p_details[$key] ) );
> 
482c515,516
< 
---
> //break;
> $success = FALSE; // force failure/rollback
488c522,523
< 		$success = commit( $db_conn );
---
> if (I_DEBUG) pre_echo( "COMMIT" );
> 		$success = commit( $db_conn );	// commit transaction
494a530
> if (I_DEBUG) pre_echo( "ROLLBACK" );
499a536
> if (I_DEBUG) pre_echo( "ROLLBACK" );
