<!DOCTYPE HTML>
<?PHP
	include 'functions.php';
	check_logon();
	connect();
	$lastyear = date("Y", time())-1;

	if(isset($_POST['div_distribute'])){
		
		//Sanitize user input
		$div_value = sanitize($_POST['div_value']);
		$div_type = sanitize($_POST['div_type']);
		$div_year = sanitize($_POST['div_year']);
		$timestamp = time();
		
		//Calculate UNIX TIMESTAMP for first and last day of selected year
		$div_year_beg = mktime(0, 0, 0, 1, 1, $div_year);
		$div_year_end = mktime(0, 0, 0, 1, 0, ($div_year+1));
		
		//Get all active customers in array
		$sql_cust = "SELECT cust_id FROM customer WHERE cust_active = 1";
		$query_cust = mysql_query($sql_cust);
		check_sql($query_cust);
		$cust = array();
		while($row_cust = mysql_fetch_assoc($query_cust)){
			$cust[] = $row_cust;
		}
		
		//Get all shares in array
		$sql_sh = "SELECT * FROM shares WHERE cust_id IN (SELECT cust_id FROM customer WHERE cust_active = 1) AND share_date < $div_year_end";
		$query_sh = mysql_query($sql_sh);
		check_sql($query_sh);
		$shares = array();
		$share_count = 0;
		while ($row_sh = mysql_fetch_assoc($query_sh)){
			$shares[] = $row_sh;
			$share_count = $share_count + $row_sh['share_amount'];
		}
		
		//If entered dividend value is grand total, divide that amount by the number of eligble shares
		if($div_type == 2) $div_value = ceil($div_value / $share_count);
		
		$div_fact = 0;
		$div_total = 0;
		foreach ($cust as $c){
			foreach ($shares as $s){
				if ($s['cust_id'] == $c['cust_id']){
					if ($s['share_date'] < $div_year_beg) $div_fact = $div_fact + $s['share_amount'];
					else $div_fact = $div_fact + $s['share_amount'] * round(ceil(($div_year_end - $s['share_date'])/86400)/365,2);
				}
			}
			
			//Calculate dividend for current customer
			$div_cust = round($div_fact * $div_value,0);
			
			//Insert dividend in SAVINGS
			$sql_cust_div = "INSERT INTO savings (cust_id, sav_date, sav_amount, savtype_id, sav_created, user_id) VALUES ($c[cust_id], $div_year_end, $div_cust, 9, $timestamp, $_SESSION[log_id])";
			$query_cust_div = mysql_query($sql_cust_div);
			check_sql($query_cust_div);
			
			$div_total = $div_total + $div_cust;
			$div_fact=0;
		}
		
		//Insert grand total distributed dividend into EXPENDITURES
		$sql_div_exp = "INSERT INTO expenditures (exptype_id, exp_amount, exp_date, exp_text, exp_created, user_id) VALUES (18, $div_total, $div_year_end, 'Distributed Dividend for $div_year', $timestamp, $_SESSION[log_id])";
		$query_div_exp = mysql_query($sql_div_exp);
		check_sql($query_div_exp );
	}
?>


<html>
	<?PHP htmlHead('Dividend',1) ?>	
	
	<body>
	
		<!-- MENU -->
		<?PHP 
				include_Menu(4);
		?>
	
		<!-- MENU MAIN -->
		<div id="menu_main">
			<a href="start.php">Back</a>
			<a href="books_expense.php">New Expense</a>
			<a href="books_income.php">New Income</a>
			<a href="books_dividend.php" id="item_selected">Share Dividend</a>
		</div>
		
		<div class="content_center">
			<p class="heading">Distribute Share Dividend</p>
			<form action="books_dividend.php" method="post">
				<input type="number" name="div_year" min="2000" max="<?PHP echo $lastyear; ?>" placeholder="Enter Year" value="<?PHP echo $lastyear; ?>"/>
				<br/><br/>
				<select name="div_type">
					<option value="1">Dividend per share</option>
					<option value="2">Grand Total Dividend</option>
				</select>
				<br/><br/>
				<input type="number" name="div_value" placeholder="<?PHP echo $_SESSION['set_cur']; ?>" />
				<br/><br/>
				<input type="submit" name="div_distribute" value="Distribute Dividend" />
			</form>
		</div>