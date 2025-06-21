<!-- EDIT MODAL -->
<div class="fixed inset-0 bg-black/50 z-40 hidden flex items-center justify-center" id="editModalOverlay">
    <!-- Modal container -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 w-11/12 max-w-5xl max-h-[90vh] overflow-y-auto relative"
        id="EditForm">
        <!-- Close button -->
        <button onclick="CloseButton()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
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

        <h2 class="text-xl font-bold text-gray-700 mb-4">Edit Student Information</h2>
        <p class="text-sm text-gray-600 mb-4"></p>
        <div class="container mx-auto px-4 py-8">
            <div class="bg-white shadow-md rounded-lg p-6 mb-8">
                <!-- Logo and Header -->

                <form id="editStudentForm" method="POST" class="space-y-6" enctype="multipart/form-data">
                    <!-- Basic Information Section -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Basic Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="editStudentNumber" class="block text-sm font-medium text-gray-700">Student
                                    Number</label>
                                <div class="flex">
                                    <input type="text" id="editStudentNumber" name="studentNumber" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 bg-gray-100"
                                        readonly>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="editEntryDate" class="block text-sm font-medium text-gray-700">Entry
                                    Date</label>
                                <input type="date" id="editEntryDate" name="entryDate"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" value=""
                                    required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="mb-4">
                                <label for="editLastName" class="block text-sm font-medium text-gray-700">Last
                                    Name</label>
                                <input type="text" id="editLastName" name="lastName"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="editFirstName" class="block text-sm font-medium text-gray-700">First
                                    Name</label>
                                <input type="text" id="editFirstName" name="firstName"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="editMiddleName" class="block text-sm font-medium text-gray-700">Middle
                                    Name</label>
                                <input type="text" id="editMiddleName" name="middleName"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="editBirthdate"
                                    class="block text-sm font-medium text-gray-700">Birthdate</label>
                                <input type="date" id="editBirthdate" name="birthdate"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="editCivilStatus" class="block text-sm font-medium text-gray-700">Civil
                                    Status</label>
                                <select id="editCivilStatus" name="civilStatus"
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
                                <label for="editUserEmail" class="block text-sm font-medium text-gray-700">Email
                                    Address</label>
                                <input type="email" id="editUserEmail" name="user_email"
                                    placeholder="ZerohanDev@gmail.com"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="editUserContact" class="block text-sm font-medium text-gray-700">Contact
                                    Number</label>
                                <input type="text" id="editUserContact" name="user_contact" placeholder="+63"
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
                                <label for="editRegion" class="block text-sm font-medium text-gray-700">Region</label>
                                <select id="editRegion"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Region</option>
                                </select>
                                <input type="hidden" id="edit-current-region-name" name="region_name">
                            </div>

                            <div class="mb-4">
                                <label for="editProvince"
                                    class="block text-sm font-medium text-gray-700">Province</label>
                                <select id="editProvince"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Province</option>
                                </select>
                                <input type="hidden" id="edit-current-province-name" name="province_name">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="editCity"
                                    class="block text-sm font-medium text-gray-700">Municipality/City</label>
                                <select id="editCity" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2"
                                    required>
                                    <option value="">Select City</option>
                                </select>
                                <input type="hidden" id="edit-current-city-name" name="city_name">
                            </div>

                            <div class="mb-4">
                                <label for="editBarangay"
                                    class="block text-sm font-medium text-gray-700">Barangay</label>
                                <select id="editBarangay"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Barangay</option>
                                </select>
                                <input type="hidden" id="edit-current-barangay-name" name="barangay_name">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="editPurok" class="block text-sm font-medium text-gray-700">Purok
                                (Optional)</label>
                            <input type="text" id="editPurok" name="purok"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2">
                        </div>
                    </div>


                    <!-- Place of Birth -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Place of Birth</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="editBirthRegion"
                                    class="block text-sm font-medium text-gray-700">Region</label>
                                <select id="editBirthRegion" name="birthRegion"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Region</option>
                                </select>
                                <input type="hidden" id="edit-birth-region-name" name="birth_region_name">
                            </div>

                            <div class="mb-4">
                                <label for="editBirthProvince"
                                    class="block text-sm font-medium text-gray-700">Province</label>
                                <select id="editBirthProvince" name="birthProvince"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Province</option>
                                </select>
                                <input type="hidden" id="edit-birth-province-name" name="birth_province_name">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="editBirthCity"
                                    class="block text-sm font-medium text-gray-700">Municipality/City</label>
                                <select id="editBirthCity" name="birthCity"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select City</option>
                                </select>
                                <input type="hidden" id="edit-birth-city-name" name="birth_city_name">
                            </div>

                            <div class="mb-4">
                                <label for="editBirthBrgy"
                                    class="block text-sm font-medium text-gray-700">Barangay</label>
                                <select id="editBirthBrgy" name="birthBrgy"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Barangay</option>
                                </select>
                                <input type="hidden" id="edit-birth-barangay-name" name="birth_barangay_name">
                            </div>
                        </div>
                    </div>

                    <!-- Educational Background -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Educational Background</h3>

                        <div class="mb-4">
                            <label for="editEducationalAttainment"
                                class="block text-sm font-medium text-gray-700">Educational Attainment
                                Before Training</label>
                            <select id="editEducationalAttainment" name="educationalAttainment"
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
                            <label for="editClassification"
                                class="block text-sm font-medium text-gray-700">Learner/Trainee/Student
                                Classification</label>
                            <select id="editClassification" name="classification"
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
                                <label for="editEmergencyName" class="block text-sm font-medium text-gray-700">Complete
                                    Name</label>
                                <input type="text" id="editEmergencyName" name="emergencyName"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="editRelationship"
                                    class="block text-sm font-medium text-gray-700">Relationship</label>
                                <select id="editRelationship" name="relationship"
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
                                <label for="editEmergencyEmail"
                                    class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="editEmergencyEmail" name="emergencyEmail"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>

                            <div class="mx-2 flex flex-col w-full">
                                <label for="editEmergencyContact"
                                    class="block text-sm font-medium text-gray-700">Contact
                                    Number</label>
                                <input type="tel" id="editEmergencyContact" name="emergencyContact" maxlength="11"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                            </div>
                        </div>

                        <div class="mb-4 flex items-center">
                            <input type="checkbox" id="editSameAsAbove" name="sameAsAbove"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            <label for="editSameAsAbove" class="ml-2 block text-sm text-gray-700">Same address as
                                student</label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="editEmergencyRegion"
                                    class="block text-sm font-medium text-gray-700">Region</label>
                                <select id="editEmergencyRegion" name="emergencyRegion"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Region</option>
                                </select>
                                <input type="hidden" id="edit-emergency-region-name" name="emergency_region_name">
                            </div>

                            <div class="mb-4">
                                <label for="editEmergencyProvince"
                                    class="block text-sm font-medium text-gray-700">Province</label>
                                <select id="editEmergencyProvince" name="emergencyProvince"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Province</option>
                                </select>
                                <input type="hidden" id="edit-emergency-province-name" name="emergency_province_name">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="editEmergencyCity"
                                    class="block text-sm font-medium text-gray-700">Municipality/City</label>
                                <select id="editEmergencyCity" name="emergencyCity"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select City</option>
                                </select>
                                <input type="hidden" id="edit-emergency-city-name" name="emergency_city_name">
                            </div>

                            <div class="mb-4">
                                <label for="editEmergencyBrgy"
                                    class="block text-sm font-medium text-gray-700">Barangay</label>
                                <select id="editEmergencyBrgy" name="emergencyBrgy"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
                                    <option value="">Select Barangay</option>
                                </select>
                                <input type="hidden" id="edit-emergency-barangay-name" name="emergency_barangay_name">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="editEmergencyPurok" class="block text-sm font-medium text-gray-700">Purok
                                (Optional)</label>
                            <input type="text" id="editEmergencyPurok" name="emergencyPurok"
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
                                    <input type="radio" id="editReferralNone" name="referral" value="none"
                                        class="h-4 w-4 text-blue-600 border-gray-300" checked>
                                    <label for="editReferralNone" class="ml-2 block text-sm text-gray-700">None</label>
                                </div>

                                <div class="flex items-center mb-2">
                                    <input type="radio" id="editReferralOther" name="referral" value="other"
                                        class="h-4 w-4 text-blue-600 border-gray-300">
                                    <label for="editReferralOther"
                                        class="ml-2 block text-sm text-gray-700">Other</label>
                                </div>

                                <div id="editOtherReferralDiv" class="mt-2" style="display: none;">
                                    <input type="text" id="editOtherReferral" name="otherReferral"
                                        class="block w-full border-gray-300 rounded-md shadow-sm p-2"
                                        placeholder="Please specify">
                                </div>

                                <script>
                                    document.getElementById('editReferralOther').addEventListener('change', function () {
                                        document.getElementById('editOtherReferralDiv').style.display = this.checked ? 'block' : 'none';
                                    });

                                    document.getElementById('editReferralNone').addEventListener('change', function () {
                                        document.getElementById('editOtherReferralDiv').style.display = 'none';
                                    });
                                </script>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="editKnowledgeSource" class="block text-sm font-medium text-gray-700">I
                                was able to know CAREPRO through:</label>
                            <select id="editKnowledgeSource" name="knowledgeSource"
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
                                <input type="checkbox" id="editReqBirthCert" name="requirements[]"
                                    value="PSA Birth Certificate" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label for="editReqBirthCert" class="ml-2 block text-sm text-gray-700">PSA Birth
                                    Certificate</label>
                            </div>

                            <div class="flex items-center mb-2">
                                <input type="checkbox" id="editReqTOR" name="requirements[]"
                                    value="TOR, ALS cert or FORM 137"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label for="editReqTOR" class="ml-2 block text-sm text-gray-700">TOR, ALS
                                    Certificate or FORM 137</label>
                            </div>

                            <div class="flex items-center mb-2">
                                <input type="checkbox" id="editReqPhotos" name="requirements[]"
                                    value="Passport size photos" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <label for="editReqPhotos" class="ml-2 block text-sm text-gray-700">Passport
                                    size photos</label>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Upload -->
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-medium mb-4">Photo</h3>

                        <div class="mb-4">
                            <div class="flex items-center">
                                <div id="editCurrentPhotoPreview" class="mr-4">
                                    <img id="editCurrentStudentPhoto"
                                        class="h-32 w-32 object-cover rounded-md border border-gray-300"
                                        alt="Current Photo">
                                </div>
                                <div>
                                    <label for="editPhotoUpload" class="block text-sm font-medium text-gray-700">Update
                                        ID Photo
                                        (2x2)</label>
                                    <input type="file" id="editPhotoUpload" name="photoUpload"
                                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        accept="image/*">
                                    <div id="editPhotoPreview" class="mt-2 hidden">
                                        <img id="editPreviewImage"
                                            class="h-32 w-32 object-cover rounded-md border border-gray-300"
                                            alt="Preview">
                                    </div>

                                    <script>
                                        document.getElementById('editPhotoUpload').addEventListener('change', function (event) {
                                            const file = event.target.files[0];
                                            if (file) {
                                                const reader = new FileReader();
                                                reader.onload = function (e) {
                                                    document.getElementById('editPreviewImage').src = e.target.result;
                                                    document.getElementById('editPhotoPreview').classList.remove('hidden');
                                                }
                                                reader.readAsDataURL(file);
                                            }
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeEditModal3()"
                            class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        <button type="submit" id="updateButton"
                            class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Update Student Information
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>