<?php
  include 'connect.php';
  class searchhotel extends connect
  {
  	public function __construct()
	{
	  parent::__construct();
	}
	public function allsearch()
	{
	  if($this->db_handle)
	  {
	  	$result=mysqli_query($this->db_handle,"select * from hotel");
		print"<table border=1>
		<tr>
			<input type=text name=t1 placeholder=AllSearch required>
			<th>Hotel Code</th>
			<th>Hotel Name</th>
			<th>Address</th>
			<th>PostCode</th>
			<th>City</th>
			<th>Country</th>
			<th>Number of Rooms</th>
			<th>Phone Number</th>
			<th>Star Rating</th>
		</tr>";
		while($db_field=mysqli_fetch_assoc($result))
		{
			print"<tr>";
			print"<td>".$db_field['hotelcode']."</td>";
			print"<td>".$db_field['hotelname']."</td>";
			print"<td>".$db_field['address']."</td>";
			print"<td>".$db_field['postcode']."</td>";
			print"<td>".$db_field['city']."</td>";
			print"<td>".$db_field['country']."</td>";
			print"<td>".$db_field['numberofroom']."</td>";
			print"<td>".$db_field['phone_no']."</td>";
			print"<td>".$db_field['star_rating']."</td>";
			
		}
	   }
	}
  }
$ob=new searchhotel();
if(isset($_REQUEST["b5"]))
$ob->allsearch();
?>