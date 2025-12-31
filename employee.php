
<?php
include 'connect.php';
class employee extends connect
{
  public $a,$b,$c,$d,$e,$f,$g,$h,$i,$j,$k;
  public function __construct()
  {
	parent::__construct();
  }
  public function save()
  {
   	if($this->db_handle)
	{
  		$k=0;
		$result=mysqli_query($this->db_handle, "select employeeid from employee");
		while($db_field=mysqli_fetch_assoc($result))
		{
			if($db_field['employeeid']==$_POST["t1"])

			{
			     $k=1;
			     break;
			}
		}
			if($k==0)
			{
				$s="insert into employee values('$_POST[t1]','$_POST[t2]','$_POST[t3]','$_POST[t4]','$_POST[t5]','$_POST[t6]','$_POST[t7]','$_POST[t8]','$_POST[t9]','$_POST[t10]','$_POST[t11]')";
	  			mysqli_query($this->db_handle , $s);
	 			echo"<script language=javascript>alert('Record Save')</script>";
	  			echo"<script>window.open('employee.html',_self)</script>";
			}
			else
			echo"<script language=javascript>alert('Employee ID Already Exist')</script>";				
	}
	else
	{
	  echo"<script language=javascript>alert('Database Not Found')</script>";
	}
  }
  public function delete()
  {
	if($this->db_handle)
	{
	  $s="delete from employee where employeeid='$_POST[t1]'";
	  mysqli_query($this->db_handle,$s);
	  echo"<script language=javascript>alert('Record Delete')</script>";
	  echo"<script>window.open('employee.html',_self)</script>";
	}
	else
	echo"<script language=javascript>alert('Database Not Found')</script>";
  }
  public function search()
  {
  	if($this->db_handle)
	{
		$result=mysqli_query($this->db_handle, "select * from employee where employeeid='$_POST[t1]'");
		while($db_field=mysqli_fetch_assoc($result))
		{
			$this->a=$db_field['employeeid'];
			$this->b=$db_field['hotelcode'];
			$this->c=$db_field['roleid'];
			$this->d=$db_field['firstname'];
			$this->e=$db_field['lastname'];
			$this->f=$db_field['dob'];
			$this->g=$db_field['gender'];
			$this->h=$db_field['phoneno'];
			$this->i=$db_field['email'];
			$this->j=$db_field['password'];
			$this->k=$db_field['salary'];
		}
	}
  }
  public function update()
  {
  	if($this->db_handle)
	{
	  $s="update employee set hotelcode='$_POST[t2]', roleid='$_POST[t3]', firstname='$_POST[t4]', lastname='$_POST[t5]', dob='$_POST[t6]', gender='$_POST[t7]', phoneno='$_POST[t8]', email='$_POST[t9]', password='$_POST[t10]', salary='$_POST[t11]' where employeeid='$_POST[t1]'";
	  mysqli_query($this->db_handle , $s);
 	  echo"<script language=javascript>alert('Record Updated')</script>";
	  echo"<script>window.open('employee.html',_self)</script>";
	}
	else
	  echo"<script language=javascript>alert('Database not found')</script>";
  }
  public function getrole()
    {
        if ($this->db_handle) 
	{
            $result = mysqli_query($this->db_handle, "select roleid from role");
            $roles = array();
            while ($row = mysqli_fetch_assoc($result)) 
		{
                $roles[] = $row['roleid'];
            	}
            return $roles;
		
        } 
	else 
	{
            return null;
        }
    }

    public function gethotel()
    {
        if ($this->db_handle) 
	{
            $result = mysqli_query($this->db_handle, "select hotelcode from hotel");
            $hotels = array();
            while ($row = mysqli_fetch_assoc($result)) 
		{
                $hotels[] = $row['hotelcode'];
            	}
            return $hotels;
		
        } 
	else 
	{
            return null;
        }
    }

  
   
}
$ob=new employee();
if(isset($_REQUEST["b1"]))
$ob->save();
if(isset($_REQUEST["b2"]))
$ob->delete();
if(isset($_REQUEST["b3"]))
$ob->search();
if(isset($_REQUEST["b4"]))
$ob->update();
?>

<form name=p method=post action='employee.php'>
<head>
<style>
  body{
background-image:"back16.jpg";
background-repeat:no-repeat;
background-size: 100% 100%;
 }
 .form-container {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    background-color: transparent;
    color: black;
    width: 43%;
    box-shadow: 0px 0px 20px 0px black;
    border: 1px solid black;
    float: center; 
    margin: 30px 40%px;
    font-size: 17px;
}
.title {
    font-size: 1rem;
    margin-bottom: 1rem;
    margin-top:1rem;
    font-family:sans-sarif;
    background-color: black;
    color: white;
    width: 8em;
    text-align: center;
} 
input[type=text] {
  width: 100%;
  padding: 10px 20px;
  margin: 2px 0;
  box-sizing: border-box;
  background-color: white;
  color: black;
}

input[type=email] {
  width: 100%;
  padding: 10px 20px;
  margin: 2px 0;
  box-sizing: border-box;
  background-color: white;
  color: black;
}

.form-container  .option{
  width: 100%;
  margin: 2px 0;
  box-sizing: border-box;
  background-color: white;
  color: black;
}

.m{
  margin-right: 20px;
  margin-left: 20px;
 }

.button {
  background-color: #000;
  border: black;
  color: white;
  width: 100%;
  padding: 10px 25px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 12px;
  margin: 6px 18px 10px;
  cursor: pointer;
}

</style>

<meta charset="ISO-8859-1">
<title>Insert title here</title>
</head>
<body>
<div class="form-container">
<h3 class="title">EMPLOYEE</h3>
<table>
<tr>
<th><p align=left class="m"><font color=black>Employee ID</font></th>
<th><input type=text name=t1 value=<?php echo $ob->b; ?>></th>
<th rowspan=6><input type=button class="button" value=Home onclick="menu()"><br>
<input type=submit class="button" value=Save name=b1><br>
<input type=submit class="button" value=Delete name=b2><br>
<input type=submit class="button" value=Search name=b3><br>
<input type=submit class="button" value=Update name=b4><br>
<input type=button class="button" value=New onclick="a()"></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Hotel Code</font></th>
<th><select name=t2 class=option value=<?php echo $ob->b; ?>>
<option>Select
<?php
            $hotels = $ob->gethotel();

            if ($hotels) 
		{
                foreach ($hotels as $hotel) 
		{
                    echo '<option value="' . $hotel . '">' . $hotel . '</option>';
                }
            }
        ?>
    </select></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Role ID</font></th>
<th><select name=t3 class=option value=<?php echo $ob->c; ?>>
<option>Select
<?php
            $roles = $ob->getrole();

            if ($roles) 
		{
                foreach ($roles as $role) 
		{
                    echo '<option value="' . $role . '">' . $role . '</option>';
                }
            }
        ?>
    </select></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>First Name</font></th>
<th><input type=text name=t4 value=<?php echo $ob->d; ?>></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Last Name</font></th>
<th><input type=text name=t5 value=<?php echo $ob->e; ?>></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Date Of Birth</font></th>
<th><input type=text name=t6 value=<?php echo $ob->f; ?>></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Gender</font></th>
<th><input type=radio name=t7 value="Male" value=<?php echo $ob->g; ?>>Male
<input type=radio name=t7 value="Female" value=<?php echo $ob->g; ?>>Female</th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Phone Number</font></th>
<th><input type=text name=t8 value=<?php echo $ob->h; ?>></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Email</font></th>
<th><input type=email name=t9 value=<?php echo $ob->i; ?>></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Password</font></th>
<th><input type=text name=t10 value=<?php echo $ob->j; ?>></th>
</tr>
<tr>
<th><p align=left class="m"><font color=black>Salary</font></th>
<th><input type=text name=t11 value=<?php echo $ob->k; ?>></th>
</tr>
<tr>
</tr>
</div>
</form>
<tr>
<form name=f method=post action=searchemployee.php>
<th><input type=submit class='button' value=Allserach name=b5></th>
</form>
</tr>
</table>
</body>
