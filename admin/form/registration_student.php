<!-- INSERT MODAL -->
<div class="fixed inset-0 bg-black/50 z-40 hidden flex items-center justify-center" id="modalOverlay">
    <!-- Modal container -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 w-11/12 max-w-5xl max-h-[90vh] overflow-y-auto relative"
        id="RegForm">
        <!-- Close button -->
        <button id="closeModalBtn" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Form header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <div class="w-12 h-12 mr-4">
                    <img src="../images/logo.png" alt="Care Pro Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-green-600">CARE PRO</h1>
                    <p class="text-sm text-gray-500">TRAINING AND ASSESSMENT CENTER</p>
                </div>
            </div>


        </div>

        <h2 class="text-xl font-bold text-gray-700 mb-4">Enrollment Registration Form</h2>
        <p class="text-sm text-gray-600 mb-4"></p>
        <div class="container mx-auto px-4 py-8">
            <div class="bg-white shadow-md rounded-lg p-6 mb-8">
                <!-- Logo and Header -->

                <form id="registrationForm" method="POST" class="space-y-6" enctype="multipart/form-data">
                    <!-- Basic Information Section -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Basic Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="studentNumber" class="block text-sm font-medium text-gray-700">Student
                                    Number</label>
                                <div class="flex">
                                    <input type="text" id="" name="studentNumber" required
                                        value="<?php echo $newStudentNumber ?>"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 bg-gray-100"
                                        readonly>

                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="entryDate" class="block text-sm font-medium text-gray-700">Entry
                                    Date</label>
                                <input type="date" id="entryDate" name="entryDate"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" value=""
                                    required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="mb-4">
                                <label for="lastName" class="block text-sm font-medium text-gray-700">Last
                                    Name</label>
                                <input type="text" id="lastName" name="lastName"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="firstName" class="block text-sm font-medium text-gray-700">First
                                    Name</label>
                                <input type="text" id="firstName" name="firstName"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="middleName" class="block text-sm font-medium text-gray-700">Middle
                                    Name</label>
                                <input type="text" id="middleName" name="middleName"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="birthdate" class="block text-sm font-medium text-gray-700">Birthdate</label>
                                <input type="date" id="birthdate" name="birthdate"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="civilStatus" class="block text-sm font-medium text-gray-700">Civil
                                    Status</label>
                                <select id="civilStatus" name="civilStatus"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Civil Status</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="divorced">Divorced</option>
                                    <option value="widowed">Widowed</option>
                                    <option value="separated">Separated</option>
                                </select>
                            </div>
                        </div>
                        <h3 class="text-lg font-medium mb-4">Contact Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="birthdate" class="block text-sm font-medium text-gray-700">Email
                                    Address</label>
                                <input type="email" name="user_email" placeholder="ZerohanDev@gmail.com"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="birthdate" class="block text-sm font-medium text-gray-700">Contact
                                    Number</label>
                                <input type="text" name="user_contact" placeholder="+63"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" maxlength="11"
                                    required>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Current Address</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="region" class="block text-sm font-medium text-gray-700">Region</label>
                                <select id="region" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2"
                                    required>
                                    <option value="">Select Region</option>
                                </select>
                                <input type="hidden" id="current-region-name" name="region_name">
                            </div>

                            <div class="mb-4">
                                <label for="province" class="block text-sm font-medium text-gray-700">Province</label>
                                <select id="province" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2"
                                    required>
                                    <option value="">Select Province</option>
                                </select>
                                <input type="hidden" id="current-province-name" name="province_name">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="city"
                                    class="block text-sm font-medium text-gray-700">Municipality/City</label>
                                <select id="city" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2"
                                    required>
                                    <option value="">Select City</option>
                                </select>
                                <input type="hidden" id="current-city-name" name="city_name">
                            </div>

                            <div class="mb-4">
                                <label for="barangay" class="block text-sm font-medium text-gray-700">Barangay</label>
                                <select id="barangay" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2"
                                    required>
                                    <option value="">Select Barangay</option>
                                </select>
                                <input type="hidden" id="current-barangay-name" name="barangay_name">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="purok" class="block text-sm font-medium text-gray-700">Purok (Optional)</label>
                            <input type="text" id="purok" name="purok"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                        </div>
                    </div>


                    <!-- Place of Birth -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Place of Birth</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="birthRegion" class="block text-sm font-medium text-gray-700">Region</label>
                                <select id="birthRegion" name="birthRegion"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Region</option>
                                </select>
                                <input type="hidden" id="birth-region-name" name="birth_region_name">
                            </div>

                            <div class="mb-4">
                                <label for="birthProvince"
                                    class="block text-sm font-medium text-gray-700">Province</label>
                                <select id="birthProvince" name="birthProvince"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Province</option>
                                </select>
                                <input type="hidden" id="birth-province-name" name="birth_province_name">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="birthCity"
                                    class="block text-sm font-medium text-gray-700">Municipality/City</label>
                                <select id="birthCity" name="birthCity"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select City</option>
                                </select>
                                <input type="hidden" id="birth-city-name" name="birth_city_name">
                            </div>

                            <div class="mb-4">
                                <label for="birthBrgy" class="block text-sm font-medium text-gray-700">Barangay</label>
                                <select id="birthBrgy" name="birthBrgy"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Barangay</option>
                                </select>
                                <input type="hidden" id="birth-barangay-name" name="birth_barangay_name">
                            </div>
                        </div>
                    </div>

                    <!-- Educational Background -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Educational Background</h3>

                        <div class="mb-4">
                            <label for="educationalAttainment"
                                class="block text-sm font-medium text-gray-700">Educational Attainment
                                Before Training</label>
                            <select id="educationalAttainment" name="educationalAttainment"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                <option value="">Select Educational Attainment</option>
                                <option value="Elementary Undergraduate">Elementary Undergraduate</option>
                                <option value="Elementary Graduate">Elementary Graduate</option>
                                <option value="High School Undergraduate">High School Undergraduate</option>
                                <option value="High School Graduate">High School Graduate</option>
                                <option value="College Undergraduate">College Undergraduate</option>
                                <option value="College Graduate">College Graduate</option>
                                <option value="Post Graduate">Post Graduate</option>
                                <option value="ALS Passer">ALS Passer</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="classification"
                                class="block text-sm font-medium text-gray-700">Learner/Trainee/Student
                                Classification</label>
                            <select id="classification" name="classification"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                <option value="">Select Classification</option>
                                <option value="Regular Student">Regular Student</option>
                                <option value="Working Student">Working Student</option>
                                <option value="Out of School Youth">Out of School Youth</option>
                                <option value="Person with Disability">Person with Disability</option>
                                <option value="Senior Citizen">Senior Citizen</option>
                                <option value="Indigenous People">Indigenous People</option>
                                <option value="Solo Parent">Solo Parent</option>
                            </select>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Emergency Contact</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="emergencyName" class="block text-sm font-medium text-gray-700">Complete
                                    Name</label>
                                <input type="text" id="emergencyName" name="emergencyName"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="relationship"
                                    class="block text-sm font-medium text-gray-700">Relationship</label>
                                <select id="relationship" name="relationship"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Relationship</option>
                                    <option value="Parent">Parent</option>
                                    <option value="Sibling">Sibling</option>
                                    <option value="Spouse">Spouse</option>
                                    <option value="Child">Child</option>
                                    <option value="Relative">Relative</option>
                                    <option value="Friend">Friend</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4 flex flex-row w-full">
                            <div class="mx-2 flex flex-col w-full">
                                <label for="emergencyEmail"
                                    class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="emergencyEmail" name="emergencyEmail"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mx-2 flex flex-col w-full">
                                <label for="emergencyContact" class="block text-sm font-medium text-gray-700">Contact
                                    Number</label>
                                <input type="tel" id="emergencyContact" name="emergencyContact" maxlength="11"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>
                        </div>

                        <div class="mb-4 flex items-center">
                            <input type="checkbox" id="sameAsAbove" name="sameAsAbove"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            <label for="sameAsAbove" class="ml-2 block text-sm text-gray-700">Same address as
                                student</label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="emergencyRegion"
                                    class="block text-sm font-medium text-gray-700">Region</label>
                                <select id="emergencyRegion" name="emergencyRegion"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Region</option>
                                </select>
                                <input type="hidden" id="emergency-region-name" name="emergency_region_name">
                            </div>

                            <div class="mb-4">
                                <label for="emergencyProvince"
                                    class="block text-sm font-medium text-gray-700">Province</label>
                                <select id="emergencyProvince" name="emergencyProvince"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Province</option>
                                </select>
                                <input type="hidden" id="emergency-province-name" name="emergency_province_name">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="emergencyCity"
                                    class="block text-sm font-medium text-gray-700">Municipality/City</label>
                                <select id="emergencyCity" name="emergencyCity"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select City</option>
                                </select>
                                <input type="hidden" id="emergency-city-name" name="emergency_city_name">
                            </div>

                            <div class="mb-4">
                                <label for="emergencyBrgy"
                                    class="block text-sm font-medium text-gray-700">Barangay</label>
                                <select id="emergencyBrgy" name="emergencyBrgy"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Barangay</option>
                                </select>
                                <input type="hidden" id="emergency-barangay-name" name="emergency_barangay_name">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="emergencyPurok" class="block text-sm font-medium text-gray-700">Purok
                                (Optional)</label>
                            <input type="text" id="emergencyPurok" name="emergencyPurok"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                        </div>
                    </div>

                    <!-- Referral Information -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Referral Information</h3>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">I am referred/assisted
                                by:</label>
                            <div class="mt-2">
                                <div class="flex items-center mb-2">
                                    <input type="radio" id="referralNone" name="referral" value="none"
                                        class="h-4 w-4 text-blue-600 border-gray-300" checked>
                                    <label for="referralNone" class="ml-2 block text-sm text-gray-700">None</label>
                                </div>

                                <div class="flex items-center mb-2">
                                    <input type="radio" id="referralOther" name="referral" value="other"
                                        class="h-4 w-4 text-blue-600 border-gray-300">
                                    <label for="referralOther" class="ml-2 block text-sm text-gray-700">Other</label>
                                </div>

                                <div id="otherReferralDiv" class="mt-2" style="display: none;">
                                    <input type="text" id="otherReferral" name="otherReferral"
                                        class="block w-full border-gray-300 rounded-md shadow-sm p-2"
                                        placeholder="Please specify">
                                </div>

                                <script>
                                    document.getElementById('referralOther').addEventListener('change', function () {
                                        document.getElementById('otherReferralDiv').style.display = this.checked ? 'block' : 'none';
                                    });

                                    document.getElementById('referralNone').addEventListener('change', function () {
                                        document.getElementById('otherReferralDiv').style.display = 'none';
                                    });
                                </script>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="knowledgeSource" class="block text-sm font-medium text-gray-700">I
                                was able to know CAREPRO through:</label>
                            <select id="knowledgeSource" name="knowledgeSource"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                <option value="">Select Option</option>
                                <option value="Social Media">Social Media</option>
                                <option value="Friend/Relative">Friend/Relative</option>
                                <option value="School/Institution">School/Institution</option>
                                <option value="Government Agency">Government Agency</option>
                                <option value="Website">Website</option>
                                <option value="Advertisement">Advertisement</option>
                                <option value="Community Event">Community Event</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Requirements -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Student Requirements</h3>

                        <div class="mb-4">
                            <div class="flex items-center mb-2">
                                <input type="checkbox" id="reqBirthCert" name="requirements[]"
                                    value="PSA Birth Certificate" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label for="reqBirthCert" class="ml-2 block text-sm text-gray-700">PSA Birth
                                    Certificate</label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input type="checkbox" id="reqTOR" name="requirements[]"
                                    value="TOR, ALS Certificate or FORM 137"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">

                                <label class="ml-2 block text-sm text-gray-700">TOR, ALS
                                    Certificate or FORM 137</label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input type="checkbox" id="reqPhotos" name="requirements[]" value="Passport size photos"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label for="reqPhotos" class="ml-2 block text-sm text-gray-700">Passport
                                    size photos</label>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Upload -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Photo Upload</h3>

                        <div class="mb-4">
                            <label for="photoUpload" class="block text-sm font-medium text-gray-700">Upload
                                ID Photo (2x2)</label>
                            <input type="file" id="photoUpload" name="photoUpload"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                accept="image/*">
                            <div id="photoPreview" class="mt-2 hidden">
                                <img id="previewImage" class="h-32 w-32 object-cover rounded-md border border-gray-300"
                                    alt="Preview">
                            </div>

                            <script>
                                document.getElementById('photoUpload').addEventListener('change', function (event) {
                                    const file = event.target.files[0];
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = function (e) {
                                            document.getElementById('previewImage').src = e.target.result;
                                            document.getElementById('photoPreview').classList.remove('hidden');
                                        }
                                        reader.readAsDataURL(file);
                                    }
                                });
                            </script>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" id="submitButton"
                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>