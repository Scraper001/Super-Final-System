<?php include "../connection/connection.php";
include "includes/header.php";

$conn = con();

// Semaphore configuration - Add your API key here
define('SEMAPHORE_API_KEY', 'your-semaphore-api-key-here');
define('SEMAPHORE_API_URL', 'https://semaphore.co/api/v4/messages');

// Function to send SMS via Semaphore
function sendSemaphoreSMS($phone_numbers, $message) {
    $data = [
        'apikey' => SEMAPHORE_API_KEY,
        'number' => implode(',', $phone_numbers),
        'message' => $message,
        'sendername' => 'CarePro' // You can customize this
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SEMAPHORE_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $http_code == 200,
        'response' => json_decode($response, true),
        'http_code' => $http_code
    ];
}

// Function to get phone numbers based on group criteria
function getPhoneNumbers($conn, $group_type, $criteria) {
    $phone_numbers = [];
    
    switch ($group_type) {
        case 'all':
            $sql = "SELECT DISTINCT phone_number FROM student_info_tbl WHERE phone_number != '' AND phone_number IS NOT NULL";
            $result = $conn->query($sql);
            break;
            
        case 'program':
            $sql = "SELECT DISTINCT s.phone_number FROM student_info_tbl s 
                   INNER JOIN student_program sp ON s.student_id = sp.student_id 
                   WHERE sp.program_id = ? AND s.phone_number != '' AND s.phone_number IS NOT NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $criteria['program_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            break;
            
        case 'promo':
            $sql = "SELECT DISTINCT s.phone_number FROM student_info_tbl s 
                   INNER JOIN student_promo sp ON s.student_id = sp.student_id 
                   INNER JOIN promo p ON sp.promo_id = p.id 
                   WHERE p.package_name = ? AND s.phone_number != '' AND s.phone_number IS NOT NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $criteria['promo_package']);
            $stmt->execute();
            $result = $stmt->get_result();
            break;
            
        case 'location':
            $where_conditions = [];
            $params = [];
            $types = "";
            
            if (!empty($criteria['region'])) {
                $where_conditions[] = "region = ?";
                $params[] = $criteria['region'];
                $types .= "s";
            }
            if (!empty($criteria['province'])) {
                $where_conditions[] = "province = ?";
                $params[] = $criteria['province'];
                $types .= "s";
            }
            if (!empty($criteria['city'])) {
                $where_conditions[] = "city = ?";
                $params[] = $criteria['city'];
                $types .= "s";
            }
            if (!empty($criteria['brgy'])) {
                $where_conditions[] = "brgy = ?";
                $params[] = $criteria['brgy'];
                $types .= "s";
            }
            
            if (!empty($where_conditions)) {
                $sql = "SELECT DISTINCT phone_number FROM student_info_tbl 
                       WHERE " . implode(' AND ', $where_conditions) . " 
                       AND phone_number != '' AND phone_number IS NOT NULL";
                $stmt = $conn->prepare($sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $sql = "SELECT DISTINCT phone_number FROM student_info_tbl WHERE phone_number != '' AND phone_number IS NOT NULL";
                $result = $conn->query($sql);
            }
            break;
    }
    
    while ($row = $result->fetch_assoc()) {
        $phone_numbers[] = $row['phone_number'];
    }
    
    return $phone_numbers;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'send_sms') {
        // Handle SMS sending
        $announcement_id = $_POST['announcement_id'];
        $phone_numbers = json_decode($_POST['phone_numbers'], true);
        $message = $_POST['message'];
        
        $sms_result = sendSemaphoreSMS($phone_numbers, $message);
        
        if ($sms_result['success']) {
            // Update announcement record to mark as sent
            $update_stmt = $conn->prepare("UPDATE announcement SET sms_sent = 1, sms_sent_at = NOW() WHERE announcement_id = ?");
            $update_stmt->bind_param("i", $announcement_id);
            $update_stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'SMS sent successfully to ' . count($phone_numbers) . ' recipients!',
                'details' => $sms_result['response']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send SMS. Please try again.',
                'error' => $sms_result['response']
            ]);
        }
        exit;
    } else {
        // Handle announcement creation
        $announcement_id = random_int(100000, 999999);
        $user_no = $_SESSION['user_id'];
        $group_type = $_POST['group_type'];
        $message = $_POST['message'];

        // Build the group identifier based on the type
        $group = '';
        $criteria = [];
        
        switch ($group_type) {
            case 'all':
                $group = 'all_students';
                break;
            case 'program':
                $group = 'program_' . $_POST['program_id'];
                $criteria['program_id'] = $_POST['program_id'];
                break;
            case 'promo':
                $group = 'promo_' . $_POST['promo_package'];
                $criteria['promo_package'] = $_POST['promo_package'];
                break;
            case 'location':
                $location_parts = [];
                if (!empty($_POST['region'])) {
                    $location_parts[] = 'region:' . $_POST['region'];
                    $criteria['region'] = $_POST['region'];
                }
                if (!empty($_POST['province'])) {
                    $location_parts[] = 'province:' . $_POST['province'];
                    $criteria['province'] = $_POST['province'];
                }
                if (!empty($_POST['city'])) {
                    $location_parts[] = 'city:' . $_POST['city'];
                    $criteria['city'] = $_POST['city'];
                }
                if (!empty($_POST['brgy'])) {
                    $location_parts[] = 'brgy:' . $_POST['brgy'];
                    $criteria['brgy'] = $_POST['brgy'];
                }
                $group = 'location_' . implode('|', $location_parts);
                break;
        }

        // Add sms_sent column to track SMS status
        $stmt = $conn->prepare("INSERT INTO announcement (announcement_id, user_no, `group`, message, sms_sent, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->bind_param("iiss", $announcement_id, $user_no, $group, $message);

        if ($stmt->execute()) {
            // Get phone numbers for SMS confirmation
            $phone_numbers = getPhoneNumbers($conn, $group_type, $criteria);
            
            // Store data in session for SMS confirmation
            $_SESSION['pending_sms'] = [
                'announcement_id' => $announcement_id,
                'phone_numbers' => $phone_numbers,
                'message' => $message,
                'recipient_count' => count($phone_numbers)
            ];
            
            header("Location: announcement.php?status=success&show_sms_confirm=1");
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Fetch programs for dropdown
$programs_sql = "SELECT * FROM program ORDER BY program_name";
$programs_result = $conn->query($programs_sql);

// Fetch promo packages for dropdown
$promo_sql = "SELECT DISTINCT package_name FROM promo ORDER BY package_name";
$promo_result = $conn->query($promo_sql);

// Fetch unique locations for dropdowns
$regions_sql = "SELECT DISTINCT region FROM student_info_tbl WHERE region != '' ORDER BY region";
$regions_result = $conn->query($regions_sql);

$provinces_sql = "SELECT DISTINCT province FROM student_info_tbl WHERE province != '' ORDER BY province";
$provinces_result = $conn->query($provinces_sql);

$cities_sql = "SELECT DISTINCT city FROM student_info_tbl WHERE city != '' ORDER BY city";
$cities_result = $conn->query($cities_sql);

$brgys_sql = "SELECT DISTINCT brgy FROM student_info_tbl WHERE brgy != '' ORDER BY brgy";
$brgys_result = $conn->query($brgys_sql);

$conn->close();
?>

<div class="flex h-screen overflow-hidden flex-row">
    <!-- Sidebar -->
    <div id="sidebar"
        class="bg-white shadow-lg transition-all duration-300 w-[20%] flex flex-col z-10 md:flex hidden fixed min-h-screen">
        <!-- Logo -->
        <div class="px-6 py-5 flex items-center border-b border-gray-100">
            <h1 class="text-xl font-bold text-primary flex items-center">
                <i class='bx bx-plus-medical text-2xl mr-2'></i>
                <span class="text"> CarePro</span>
            </h1>
        </div>

        <?php include "includes/navbar.php" ?>
    </div>

    <!-- Main Content -->
    <div id="content" class="flex-1 flex flex-col w-[80%] md:ml-[20%] ml-0 transition-all duration-300">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="toggleSidebar"
                        class="md:hidden flex text-gray-500 hover:text-primary focus:outline-none mr-4">
                        <i class='bx bx-menu text-2xl'></i>
                    </button>
                </div>

                <div class="flex items-center space-x-4">
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
                        data-bs-toggle="modal" data-bs-target="#announcementModal">
                        <i class="fa-solid fa-bullhorn mr-1"></i>
                        New Announcement
                    </button>

                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
                        data-bs-toggle="modal" data-bs-target="#viewAnnouncementsModal">
                        <i class="fa-solid fa-envelope mr-1"></i>
                        View Announcements
                    </button>

                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer" id="logout"
                        data-swal-template="#my-template">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto no-scrollbar p-2">
            <!-- Success Message -->
            <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    Announcement saved successfully!
                </div>
            <?php endif; ?>

            <!-- Add Announcement Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                    <i class="fa-solid fa-bullhorn text-blue-600 mr-3"></i>
                    Add New Announcement
                </h2>

                <form method="POST" action="" class="space-y-6">
                    <!-- Announcement Target Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="group_type" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fa-solid fa-users mr-1"></i>
                                Target Audience
                            </label>
                            <select name="group_type" id="group_type" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">Select Target Audience</option>
                                <option value="all">All Students</option>
                                <option value="program">By Program</option>
                                <option value="promo">By Promo Package</option>
                                <option value="location">By Location</option>
                            </select>
                        </div>
                    </div>

                    <!-- Program Selection (Hidden by default) -->
                    <div id="program_section" class="hidden">
                        <label for="program_id" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fa-solid fa-graduation-cap mr-1"></i>
                            Select Program
                        </label>
                        <select name="program_id" id="program_id"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Select Program</option>
                            <?php while ($program = $programs_result->fetch_assoc()): ?>
                                <option value="<?php echo $program['id']; ?>">
                                    <?php echo htmlspecialchars($program['program_name'] . ' - ' . $program['learning_mode']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Promo Package Selection (Hidden by default) -->
                    <div id="promo_section" class="hidden">
                        <label for="promo_package" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fa-solid fa-tags mr-1"></i>
                            Select Promo Package
                        </label>
                        <select name="promo_package" id="promo_package"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Select Promo Package</option>
                            <?php while ($promo = $promo_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($promo['package_name']); ?>">
                                    <?php echo htmlspecialchars($promo['package_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Location Selection (Hidden by default) -->
                    <div id="location_section" class="hidden">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">
                                <i class="fa-solid fa-map-marker-alt mr-1"></i>
                                Location Filters (Select one or more)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="region"
                                        class="block text-sm font-medium text-gray-600 mb-1">Region</label>
                                    <select name="region" id="region"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Regions</option>
                                        <?php while ($region = $regions_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($region['region']); ?>">
                                                <?php echo htmlspecialchars($region['region']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="province"
                                        class="block text-sm font-medium text-gray-600 mb-1">Province</label>
                                    <select name="province" id="province"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Provinces</option>
                                        <?php while ($province = $provinces_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($province['province']); ?>">
                                                <?php echo htmlspecialchars($province['province']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-600 mb-1">City</label>
                                    <select name="city" id="city"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Cities</option>
                                        <?php while ($city = $cities_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($city['city']); ?>">
                                                <?php echo htmlspecialchars($city['city']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="brgy"
                                        class="block text-sm font-medium text-gray-600 mb-1">Barangay</label>
                                    <select name="brgy" id="brgy"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Barangays</option>
                                        <?php while ($brgy = $brgys_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($brgy['brgy']); ?>">
                                                <?php echo htmlspecialchars($brgy['brgy']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Content -->
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fa-solid fa-message mr-1"></i>
                            Announcement Message
                        </label>
                        <textarea name="message" id="message" rows="6" required
                            placeholder="Enter your announcement message here..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-vertical"></textarea>
                        <div class="mt-2 text-sm text-gray-500">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            Tip: Keep your message clear and concise for better engagement. SMS has character limits.
                        </div>
                    </div>

                    <!-- Preview Section -->
                    <div id="preview_section" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-800 mb-2">
                            <i class="fa-solid fa-eye mr-1"></i>
                            Preview
                        </h4>
                        <div id="preview_content" class="text-sm text-blue-700"></div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4">
                        <button type="button" id="preview_btn"
                            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center">
                            <i class="fa-solid fa-eye mr-2"></i>
                            Preview
                        </button>
                        <button type="submit"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <i class="fa-solid fa-paper-plane mr-2"></i>
                            Save Announcement
                        </button>
                    </div>
                </form>
            </div>
        </main>
        <?php include "includes/footer.php" ?>
    </div>
</div>

<!-- SMS Confirmation Modal -->
<div class="modal fade" id="smsConfirmModal" tabindex="-1" aria-labelledby="smsConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-blue-600 text-white">
                <h5 class="modal-title" id="smsConfirmModalLabel">
                    <i class="fa-solid fa-sms mr-2"></i>
                    Send SMS Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle mr-2"></i>
                    Your announcement has been saved successfully. Would you like to send it via SMS?
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fa-solid fa-users mr-1"></i>
                                    Recipients
                                </h6>
                                <p class="card-text">
                                    <span id="recipient_count" class="badge bg-primary"></span> students will receive this SMS
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fa-solid fa-calculator mr-1"></i>
                                    Estimated Cost
                                </h6>
                                <p class="card-text">
                                    ₱<span id="estimated_cost">0.00</span>
                                    <small class="text-muted">(₱1.00 per SMS)</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6>Message Preview:</h6>
                    <div class="border p-3 bg-light rounded">
                        <small class="text-muted">From: CarePro</small><br>
                        <span id="sms_message_preview"></span>
                    </div>
                    <small class="text-muted">
                        Character count: <span id="char_count">0</span>/160
                        <span id="char_warning" class="text-warning" style="display: none;">
                            (Message exceeds 160 characters and may be split into multiple SMS)
                        </span>
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times mr-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirm_send_sms">
                    <i class="fa-solid fa-paper-plane mr-1"></i>
                    Send SMS Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-3">
                    <h6>Sending SMS...</h6>
                    <p class="text-muted mb-0">Please wait while we send your message.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const groupTypeSelect = document.getElementById('group_type');
        const programSection = document.getElementById('program_section');
        const promoSection = document.getElementById('promo_section');
        const locationSection = document.getElementById('location_section');
        const previewBtn = document.getElementById('preview_btn');
        const previewSection = document.getElementById('preview_section');
        const previewContent = document.getElementById('preview_content');

        // Handle group type selection
        groupTypeSelect.addEventListener('change', function () {
            // Hide all sections first
            programSection.classList.add('hidden');
            promoSection.classList.add('hidden');
            locationSection.classList.add('hidden');

            // Show relevant section based on selection
            switch (this.value) {
                case 'program':
                    programSection.classList.remove('hidden');
                    document.getElementById('program_id').required = true;
                    document.getElementById('promo_package').required = false;
                    break;
                case 'promo':
                    promoSection.classList.remove('hidden');
                    document.getElementById('promo_package').required = true;
                    document.getElementById('program_id').required = false;
                    break;
                case 'location':
                    locationSection.classList.remove('hidden');
                    document.getElementById('program_id').required = false;
                    document.getElementById('promo_package').required = false;
                    break;
                default:
                    document.getElementById('program_id').required = false;
                    document.getElementById('promo_package').required = false;
                    break;
            }
        });

        // Preview functionality
        previewBtn.addEventListener('click', function () {
            const groupType = document.getElementById('group_type').value;
            const message = document.getElementById('message').value;

            if (!groupType || !message) {
                alert('Please select a target audience and enter a message.');
                return;
            }

            let targetDescription = '';
            switch (groupType) {
                case 'all':
                    targetDescription = 'All Students';
                    break;
                case 'program':
                    const programSelect = document.getElementById('program_id');
                    targetDescription = `Program: ${programSelect.options[programSelect.selectedIndex].text}`;
                    break;
                case 'promo':
                    const promoSelect = document.getElementById('promo_package');
                    targetDescription = `Promo Package: ${promoSelect.value}`;
                    break;
                case 'location':
                    const locationParts = [];
                    const region = document.getElementById('region').value;
                    const province = document.getElementById('province').value;
                    const city = document.getElementById('city').value;
                    const brgy = document.getElementById('brgy').value;

                    if (region) locationParts.push(`Region: ${region}`);
                    if (province) locationParts.push(`Province: ${province}`);
                    if (city) locationParts.push(`City: ${city}`);
                    if (brgy) locationParts.push(`Barangay: ${brgy}`);

                    targetDescription = `Location: ${locationParts.join(', ') || 'All Locations'}`;
                    break;
            }

            previewContent.innerHTML = `
                <strong>Target:</strong> ${targetDescription}<br>
                <strong>Message:</strong> ${message}
            `;
            previewSection.classList.remove('hidden');
        });

        // Show SMS confirmation modal if needed
        <?php if (isset($_GET['show_sms_confirm']) && isset($_SESSION['pending_sms'])): ?>
            const pendingSMS = <?php echo json_encode($_SESSION['pending_sms']); ?>;
            
            document.getElementById('recipient_count').textContent = pendingSMS.recipient_count;
            document.getElementById('estimated_cost').textContent = pendingSMS.recipient_count.toFixed(2);
            document.getElementById('sms_message_preview').textContent = pendingSMS.message;
            document.getElementById('char_count').textContent = pendingSMS.message.length;
            
            if (pendingSMS.message.length > 160) {
                document.getElementById('char_warning').style.display = 'inline';
            }
            
            const smsModal = new bootstrap.Modal(document.getElementById('smsConfirmModal'));
            smsModal.show();
            
            <?php unset($_SESSION['pending_sms']); ?>
        <?php endif; ?>

        // Handle SMS confirmation
        document.getElementById('confirm_send_sms').addEventListener('click', function() {
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            const smsModal = bootstrap.Modal.getInstance(document.getElementById('smsConfirmModal'));
            
            smsModal.hide();
            loadingModal.show();
            
            <?php if (isset($_SESSION['pending_sms'])): ?>
            const pendingSMS = <?php echo json_encode($_SESSION['pending_sms']); ?>;
            
            // Send SMS request
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'send_sms',
                    'announcement_id': pendingSMS.announcement_id,
                    'phone_numbers': JSON.stringify(pendingSMS.phone_numbers),
                    'message': pendingSMS.message
                })
            })
            .then(response => response.json())
            .then(data => {
                loadingModal.hide();
                
                if (data.success) {
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fa-solid fa-check-circle mr-2"></i>
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('main').insertBefore(alertDiv, document.querySelector('main').firstChild);
                } else {
                    // Show error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('main').insertBefore(alertDiv, document.querySelector('main').firstChild);
                }
                
                // Scroll to top to show the message
                window.scrollTo(0, 0);
            })
            .catch(error => {
                loadingModal.hide();
                console.error('Error:', error);
                
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                    An error occurred while sending SMS. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('main').insertBefore(alertDiv, document.querySelector('main').firstChild);
                window.scrollTo(0, 0);
            });
            <?php endif; ?>
        });
    });
</script>

<!-- Include Bootstrap for modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>