<?php
session_start();

include("../connection.php");
include("header_user.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_data = check_login($con);
$user_id = $user_data['user_id'];
$user_name = $user_data['user_name'];
$password = $user_data['password'];
$user_image = $user_data['image'];
$date = $user_data['date'];
$user_email = $user_data['user_email'];
$user_county = $user_data['county'];

$default_image = "../image/dp.png";

if (empty($user_image)) {
    $user_image = $default_image;
}
$valid_countries = [
    "Afghanistan",
    "Albania",
    "Algeria",
    "Andorra",
    "Angola",
    "Antigua and Barbuda",
    "Argentina",
    "Armenia",
    "Australia",
    "Austria",
    "Azerbaijan",
    "Bahamas",
    "Bahrain",
    "Bangladesh",
    "Barbados",
    "Belarus",
    "Belgium",
    "Belize",
    "Benin",
    "Bhutan",
    "Bolivia",
    "Bosnia and Herzegovina",
    "Botswana",
    "Brazil",
    "Brunei",
    "Bulgaria",
    "Burkina Faso",
    "Burundi",
    "Cabo Verde",
    "Cambodia",
    "Cameroon",
    "Canada",
    "Central African Republic",
    "Chad",
    "Chile",
    "China",
    "Colombia",
    "Comoros",
    "Congo (Congo-Brazzaville)",
    "Costa Rica",
    "Croatia",
    "Cuba",
    "Cyprus",
    "Czech Republic (Czechia)",
    "Democratic Republic of the Congo",
    "Denmark",
    "Djibouti",
    "Dominica",
    "Dominican Republic",
    "Ecuador",
    "Egypt",
    "El Salvador",
    "Equatorial Guinea",
    "Eritrea",
    "Estonia",
    "Eswatini",
    "Ethiopia",
    "Fiji",
    "Finland",
    "France",
    "Gabon",
    "Gambia",
    "Georgia",
    "Germany",
    "Ghana",
    "Greece",
    "Grenada",
    "Guatemala",
    "Guinea",
    "Guinea-Bissau",
    "Guyana",
    "Haiti",
    "Honduras",
    "Hungary",
    "Iceland",
    "India",
    "Indonesia",
    "Iran",
    "Iraq",
    "Ireland",
    "Israel",
    "Italy",
    "Ivory Coast",
    "Jamaica",
    "Japan",
    "Jordan",
    "Kazakhstan",
    "Kenya",
    "Kiribati",
    "Kuwait",
    "Kyrgyzstan",
    "Laos",
    "Latvia",
    "Lebanon",
    "Lesotho",
    "Liberia",
    "Libya",
    "Liechtenstein",
    "Lithuania",
    "Luxembourg",
    "Madagascar",
    "Malawi",
    "Malaysia",
    "Maldives",
    "Mali",
    "Malta",
    "Marshall Islands",
    "Mauritania",
    "Mauritius",
    "Mexico",
    "Micronesia",
    "Moldova",
    "Monaco",
    "Mongolia",
    "Montenegro",
    "Morocco",
    "Mozambique",
    "Myanmar (formerly Burma)",
    "Namibia",
    "Nauru",
    "Nepal",
    "Netherlands",
    "New Zealand",
    "Nicaragua",
    "Niger",
    "Nigeria",
    "North Korea",
    "North Macedonia",
    "Norway",
    "Oman",
    "Pakistan",
    "Palau",
    "Panama",
    "Papua New Guinea",
    "Paraguay",
    "Peru",
    "Philippines",
    "Poland",
    "Portugal",
    "Qatar",
    "Romania",
    "Russia",
    "Rwanda",
    "Saint Kitts and Nevis",
    "Saint Lucia",
    "Saint Vincent and the Grenadines",
    "Samoa",
    "San Marino",
    "Sao Tome and Principe",
    "Saudi Arabia",
    "Senegal",
    "Serbia",
    "Seychelles",
    "Sierra Leone",
    "Singapore",
    "Slovakia",
    "Slovenia",
    "Solomon Islands",
    "Somalia",
    "South Africa",
    "South Korea",
    "South Sudan",
    "Spain",
    "Sri Lanka",
    "Sudan",
    "Suriname",
    "Sweden",
    "Switzerland",
    "Syria",
    "Taiwan",
    "Tajikistan",
    "Tanzania",
    "Thailand",
    "Timor-Leste",
    "Togo",
    "Tonga",
    "Trinidad and Tobago",
    "Tunisia",
    "Turkey",
    "Turkmenistan",
    "Tuvalu",
    "Uganda",
    "Ukraine",
    "United Arab Emirates",
    "United Kingdom",
    "United States of America",
    "Uruguay",
    "Uzbekistan",
    "Vanuatu",
    "Vatican City",
    "Venezuela",
    "Vietnam",
    "Yemen",
    "Zambia",
    "Zimbabwe"
];

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $updated_fields = [];

    // Update user_name if provided
    if (!empty($_POST['user_name']) && !is_numeric($_POST['user_name'])) {
        $user_name = $_POST['user_name'];
        $updated_fields['user_name'] = $user_name;
    }

    // Update password if provided
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $updated_fields['password'] = $password;
    }

    // Update country if provided and valid
    if (!empty($_POST['county']) && in_array($_POST['county'], $valid_countries)) {
        $county = $_POST['county'];
        $updated_fields['county'] = $county;
    }

    if (!empty($_POST['user_email'])) {
        $user_email = $_POST['user_email'];
        $updated_fields['user_email'] = $user_email;
    }
    // Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $image_file = $_FILES['image'];
        $image_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array(strtolower($image_extension), $allowed_extensions)) {
            // Generate a unique name for the image file
            $image_name = uniqid() . '.' . $image_extension;
            $upload_path = '../uploads/' . $image_name;

            // Move the uploaded file to the uploads directory
            if (move_uploaded_file($image_file['tmp_name'], $upload_path)) {
                $updated_fields['image'] = $image_name;
            } else {
                echo "Error uploading image!";
            }
        } else {
            echo "Please upload a valid image file (jpg, jpeg, png, gif).";
        }
    }

    // If there are any updated fields, construct and execute the update query
    if (!empty($updated_fields)) {
        $set_clauses = [];
        $params = [];
        $types = '';

        foreach ($updated_fields as $field => $value) {
            $set_clauses[] = "$field = ?";
            $params[] = $value;
            $types .= 's'; // All fields are strings
        }

        $params[] = $user_id;
        $types .= 'i'; // The last parameter is the user_id (integer)

        // Prepare the SQL query with dynamic fields
        $sql = "UPDATE users SET " . implode(", ", $set_clauses) . " WHERE user_id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$params);

        // Execute the query and check for success
        if ($stmt->execute()) {
            echo "Profile updated successfully!";
        } else {
            echo "Error updating profile: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "No fields to update!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
</head>

<body>
    <h1>.</h1>
    <div class="card" style="margin-top: 100px">
        <div class="card-header" style="background-color: #00AAFF; margin-top: -70px">
            <p style="font-size:40px; color: #00AAFF">p</p>
        </div>
        <div class="card-body">
        <a href="./goalSetting.php"><button type="button" class="btn btn-primary" style="float: right;">
                Home
            </button></a>
            <h1 style="width: 300px;">Edit Profile</h1>
            <div class="container-fluid">
                <form method="post" enctype="multipart/form-data">
                    <img src="../uploads/<?php echo !empty($user_image) ? $user_image : $default_image; ?>"
                        alt="Profile" class="rounded-circle" width="100" height="100" style="border: 1px solid black;">

                    <div class="row g-3">
                        <div class="col">
                            <label for="user_name" class="form-label">User Name</label>
                            <input class="form-control" id="user_name" type="text" name="user_name"
                                placeholder="<?php echo $user_name; ?>" aria-label="User Name">
                        </div>
                        <div class="col">
                            <label for="password" class="form-label">Password</label>
                            <input class="form-control" id="password" type="password" name="password"
                                placeholder="New password" aria-label="Password">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col">
                            <label for="date" class="form-label">Account Creation Date</label>
                            <input class="form-control" type="text" value="<?php echo $date; ?>" readonly>
                        </div>
                        <div class="col">
                            <label for="county" class="form-label">Country</label>
                            <input class="form-control" list="datalistOptions" id="county" name="county"
                                placeholder="<?php echo $user_county; ?>">
                            <datalist id="datalistOptions">
                                <?php foreach ($valid_countries as $country) { ?>
                                    <option value="Afghanistan">
                                    <option value="Albania">
                                    <option value="Algeria">
                                    <option value="Andorra">
                                    <option value="Angola">
                                    <option value="Antigua and Barbuda">
                                    <option value="Argentina">
                                    <option value="Armenia">
                                    <option value="Australia">
                                    <option value="Austria">
                                    <option value="Azerbaijan">
                                    <option value="Bahamas">
                                    <option value="Bahrain">
                                    <option value="Bangladesh">
                                    <option value="Barbados">
                                    <option value="Belarus">
                                    <option value="Belgium">
                                    <option value="Belize">
                                    <option value="Benin">
                                    <option value="Bhutan">
                                    <option value="Bolivia">
                                    <option value="Bosnia and Herzegovina">
                                    <option value="Botswana">
                                    <option value="Brazil">
                                    <option value="Brunei">
                                    <option value="Bulgaria">
                                    <option value="Burkina Faso">
                                    <option value="Burundi">
                                    <option value="Cabo Verde">
                                    <option value="Cambodia">
                                    <option value="Cameroon">
                                    <option value="Canada">
                                    <option value="Central African Republic">
                                    <option value="Chad">
                                    <option value="Chile">
                                    <option value="China">
                                    <option value="Colombia">
                                    <option value="Comoros">
                                    <option value="Congo, Democratic Republic of the">
                                    <option value="Congo, Republic of the">
                                    <option value="Costa Rica">
                                    <option value="Croatia">
                                    <option value="Cuba">
                                    <option value="Cyprus">
                                    <option value="Czech Republic">
                                    <option value="Denmark">
                                    <option value="Djibouti">
                                    <option value="Dominica">
                                    <option value="Dominican Republic">
                                    <option value="East Timor">
                                    <option value="Ecuador">
                                    <option value="Egypt">
                                    <option value="El Salvador">
                                    <option value="Equatorial Guinea">
                                    <option value="Eritrea">
                                    <option value="Estonia">
                                    <option value="Eswatini">
                                    <option value="Ethiopia">
                                    <option value="Fiji">
                                    <option value="Finland">
                                    <option value="France">
                                    <option value="Gabon">
                                    <option value="Gambia">
                                    <option value="Georgia">
                                    <option value="Germany">
                                    <option value="Ghana">
                                    <option value="Greece">
                                    <option value="Grenada">
                                    <option value="Guatemala">
                                    <option value="Guinea">
                                    <option value="Guinea-Bissau">
                                    <option value="Guyana">
                                    <option value="Haiti">
                                    <option value="Honduras">
                                    <option value="Hungary">
                                    <option value="Iceland">
                                    <option value="India">
                                    <option value="Indonesia">
                                    <option value="Iran">
                                    <option value="Iraq">
                                    <option value="Ireland">
                                    <option value="Israel">
                                    <option value="Italy">
                                    <option value="Jamaica">
                                    <option value="Japan">
                                    <option value="Jordan">
                                    <option value="Kazakhstan">
                                    <option value="Kenya">
                                    <option value="Kiribati">
                                    <option value="Korea, North">
                                    <option value="Korea, South">
                                    <option value="Kuwait">
                                    <option value="Kyrgyzstan">
                                    <option value="Laos">
                                    <option value="Latvia">
                                    <option value="Lebanon">
                                    <option value="Lesotho">
                                    <option value="Liberia">
                                    <option value="Libya">
                                    <option value="Liechtenstein">
                                    <option value="Lithuania">
                                    <option value="Luxembourg">
                                    <option value="Madagascar">
                                    <option value="Malawi">
                                    <option value="Malaysia">
                                    <option value="Maldives">
                                    <option value="Mali">
                                    <option value="Malta">
                                    <option value="Marshall Islands">
                                    <option value="Mauritania">
                                    <option value="Mauritius">
                                    <option value="Mexico">
                                    <option value="Micronesia">
                                    <option value="Moldova">
                                    <option value="Monaco">
                                    <option value="Mongolia">
                                    <option value="Montenegro">
                                    <option value="Morocco">
                                    <option value="Mozambique">
                                    <option value="Myanmar">
                                    <option value="Namibia">
                                    <option value="Nauru">
                                    <option value="Nepal">
                                    <option value="Netherlands">
                                    <option value="New Zealand">
                                    <option value="Nicaragua">
                                    <option value="Niger">
                                    <option value="Nigeria">
                                    <option value="North Macedonia">
                                    <option value="Norway">
                                    <option value="Oman">
                                    <option value="Pakistan">
                                    <option value="Palau">
                                    <option value="Panama">
                                    <option value="Papua New Guinea">
                                    <option value="Paraguay">
                                    <option value="Peru">
                                    <option value="Philippines">
                                    <option value="Poland">
                                    <option value="Portugal">
                                    <option value="Qatar">
                                    <option value="Romania">
                                    <option value="Russia">
                                    <option value="Rwanda">
                                    <option value="Saint Kitts and Nevis">
                                    <option value="Saint Lucia">
                                    <option value="Saint Vincent and the Grenadines">
                                    <option value="Samoa">
                                    <option value="San Marino">
                                    <option value="Sao Tome and Principe">
                                    <option value="Saudi Arabia">
                                    <option value="Senegal">
                                    <option value="Serbia">
                                    <option value="Seychelles">
                                    <option value="Sierra Leone">
                                    <option value="Singapore">
                                    <option value="Slovakia">
                                    <option value="Slovenia">
                                    <option value="Solomon Islands">
                                    <option value="Somalia">
                                    <option value="South Africa">
                                    <option value="Spain">
                                    <option value="Sri Lanka">
                                    <option value="Sudan">
                                    <option value="Suriname">
                                    <option value="Sweden">
                                    <option value="Switzerland">
                                    <option value="Syria">
                                    <option value="Taiwan">
                                    <option value="Tajikistan">
                                    <option value="Tanzania">
                                    <option value="Thailand">
                                    <option value="Togo">
                                    <option value="Tonga">
                                    <option value="Trinidad and Tobago">
                                    <option value="Tunisia">
                                    <option value="Turkey">
                                    <option value="Turkmenistan">
                                    <option value="Tuvalu">
                                    <option value="Uganda">
                                    <option value="Ukraine">
                                    <option value="United Arab Emirates">
                                    <option value="United Kingdom">
                                    <option value="United States">
                                    <option value="Uruguay">
                                    <option value="Uzbekistan">
                                    <option value="Vanuatu">
                                    <option value="Vatican City">
                                    <option value="Venezuela">
                                    <option value="Vietnam">
                                    <option value="Yemen">
                                    <option value="Zambia">
                                    <option value="Zimbabwe">
                                    <?php } ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="user_email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="user_email" type="text" name="user_email"
                            placeholder="<?php echo $user_email; ?>" style="width: 940px">
                    </div>
                    <div class="col">
                        <div class="mb-3">
                            <label for="formFile" class="form-label">Change your profile picture</label>
                            <input class="form-control" type="file" id="formFile" style="width: 300px" name="image">
                        </div>
                    </div>

                    <input id="button" type="submit" value="Save"
                        style="background-color: #00AAFF; color: white; width: 90px; height: 40px; border-color: #00AAFF; border-radius: 20px"><br><br>
                </form>
            </div>
        </div>
    </div>

</body>

</html>