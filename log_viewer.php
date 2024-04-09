<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>M.O.V.E. ADMIN PANEL</title>
   <link href='https://fonts.googleapis.com/css?family=Courier+Prime' rel='stylesheet'>
   <link rel="stylesheet" href="style.css">
</head>
<body>
   <header>
       <div class="dark-mode-toggle-container">
           <label class="dark-mode-toggle-label" for="dark-mode-toggle">Dark Mode</label>
           <input type="checkbox" id="dark-mode-toggle" class="dark-mode-toggle">
       </div>
   </header>
   <div id="wrapper">
       <div id="content" style="text-align: justify;">
           <?php
           // Database connection details
           $servername = "localhost";
           $username = "root";
           $password = "H3rm!tsus";
           $database = "campus_management";

           // Create connection
           $conn = new mysqli($servername, $username, $password, $database);

           // Check connection
           if ($conn->connect_error) {
               die("Connection failed: " . $conn->connect_error);
           }

           // Excuse code translation array
           $excuse_codes = array(
               'I' => 'Enter Class',
               'O' => 'Office',
               'C' => 'Counselor',
               'c' => 'Clinic',
               'R' => 'Restroom',
               'W' => 'Water',
               'E' => 'Dismissal'
           );

           // Handle CSV file upload
           if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
               $csv_file = $_FILES['csv_file']['tmp_name'];
               $handle = fopen($csv_file, 'r');
               fgetcsv($handle); // Skip header row

               while (($data = fgetcsv($handle)) !== FALSE) {
                   $student_id = $data[0];
                   $first_name = $data[1];
                   $last_name = $data[2];

                   $sql = "INSERT INTO Students (StudentID, FirstName, LastName, Year) 
                           VALUES ($student_id, '$first_name', '$last_name', 0)";
                   $conn->query($sql);
               }
               fclose($handle);
           }

           // Handle adding a new student
           if (isset($_POST['add_student'])) {
               $student_id = $_POST['student_id'];
               $first_name = $_POST['first_name'];
               $last_name = $_POST['last_name'];

               $sql = "INSERT INTO Students (StudentID, FirstName, LastName, Year) 
                       VALUES ($student_id, '$first_name', '$last_name', 0)";
               $conn->query($sql);
           }

           // Handle removing a student
           if (isset($_POST['remove_student'])) {
               $student_id = $_POST['student_id'];

               $sql = "DELETE FROM Logs WHERE StudentID = $student_id";
               $conn->query($sql);
               $sql = "DELETE FROM Students WHERE StudentID = $student_id";
               $conn->query($sql);
           }

           // Handle moving juniors to seniors
           if (isset($_POST['move_juniors_to_seniors'])) {
               $sql = "DELETE FROM Logs WHERE StudentID IN (SELECT StudentID FROM Students WHERE Year = 1)";
               $conn->query($sql);
               $sql = "DELETE FROM Students WHERE Year = 1";
               $conn->query($sql);
               $sql = "UPDATE Students SET Year = 1";
               $conn->query($sql);
           }

           // Handle adding an expected log
           if (isset($_POST['add_expected_log'])) {
               $student_id = $_POST['student_id'];
               $reason = $_POST['reason'];
               $time = $_POST['time'];

               $sql = "INSERT INTO Logs (StudentID, Reason, Time, Expected) 
                       VALUES ($student_id, '$reason', '$time', 1)";
               $conn->query($sql);
           }

           // Handle clearing all logs
           if (isset($_POST['clear_logs'])) {
               $sql = "TRUNCATE TABLE Logs";
               $conn->query($sql);
           }

           // Fetch all students
           $sql = "SELECT StudentID, FirstName, LastName, Year FROM Students";
           $students_result = $conn->query($sql);

           // Fetch all logs with associated student information
           $sql = "SELECT l.LogID, l.Time, s.FirstName, s.LastName, l.Reason, l.Expected 
                   FROM Logs l
                   LEFT JOIN Students s ON l.StudentID = s.StudentID";
           $logs_result = $conn->query($sql);
           ?>

           <div class="section">
               <div class="section-header">Students</div>
               <table>
                   <tr><th>ID</th><th>Name</th><th>Name</th><th>Year</th></tr>
                   <?php
                   if ($students_result->num_rows > 0) {
                       while ($row = $students_result->fetch_assoc()) {
                           $year_text = $row["Year"] ? "Senior" : "Junior";
                           echo "<tr><td>" . $row["StudentID"] . "</td><td>" . $row["FirstName"] . "</td><td>" . $row["LastName"] . "</td><td>" . $year_text . "</td></tr>";
                       }
                   } else {
                       echo "<tr><td colspan='4'>No students found.</td></tr>";
                   }
                   ?>
               </table>
           </div>

           <div class="section">
               <div class="section-header">Upload Students from CSV</div>
               <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" enctype="multipart/form-data">
                   <input type="file" name="csv_file"><br>
                   <input type="submit" name="upload_csv" value="Upload CSV">
               </form>
           </div>

           <div class="section">
               <div class="section-header">Add a new student</div>
               <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                   Student ID: <input type="number" name="student_id"><br>
                   First Name: <input type="text" name="first_name"><br>
                   Last Name: <input type="text" name="last_name"><br>
                   <input type="submit" name="add_student" value="Add Student">
               </form>
           </div>

           <div class="section">
               <div class="section-header">Remove a student</div>
               <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                   Student ID: <input type="number" name="student_id"><br>
                   <input type="submit" name="remove_student" value="Remove Student">
               </form>
           </div>

           <div class="section">
               <div class="section-header">Move Juniors to Seniors</div>
               <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                   <input type="submit" name="move_juniors_to_seniors" value="Move Juniors to Seniors">
               </form>
           </div>

           <div class="section">
               <div class="section-header">Add an Expected Log</div>
               <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                   Student ID: <input type="number" name="student_id"><br>
                   Reason: <input type="text" name="reason"><br>
                   Time: <input type="text" name="time"><br>
                   <input type="submit" name="add_expected_log" value="Add Expected Log">
               </form>
           </div>

           <div class="section">
               <div class="section-header">Clear Logs</div>
               <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                   <input type="submit" name="clear_logs" value="Clear Logs">
               </form>
           </div>

           <div class="section">
               <div class="section-header">Logs</div>
               <table>
                   <tr><th>LogID</th><th>Time</th><th>Name</th><th>Reason</th><th>Expected</th></tr>
                   <?php
                   if ($logs_result->num_rows > 0) {
                       while ($row = $logs_result->fetch_assoc()) {
                           $reason_text = $excuse_codes[$row["Reason"]];
                           $expected_text = $row["Expected"] ? "Expected" : "Actual";
                           $name = ($row["FirstName"] !== NULL && $row["LastName"] !== NULL) ? $row["FirstName"] . " " . $row["LastName"] : "John Smith";
                           echo "<tr><td>" . $row["LogID"] . "</td><td>" . $row["Time"] . "</td><td>" . $name . "</td><td>" . $reason_text . "</td><td>" . $expected_text . "</td></tr>";
                       }
                   } else {
                       echo "<tr><td colspan='5'>No logs found.</td></tr>";
                   }
                   ?>
               </table>
           </div>
       </div>
   </div>
   <script src="dark-mode.js"></script>
</body>
</html>
