<!-- Add Program Modal -->
<div id="tuitionModal" class="fixed inset-0 bg-black/20 flex items-center justify-center hidden z-50">
    <form id="addProgramForm" class="bg-white p-6 rounded-xl shadow-lg w-full max-w-md h-[80%] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Add Tuition Fee</h2>

        <!-- Program -->
        <span>Select Program Here</span>
        <select id="programSelect" name="program" class="w-full px-3 py-2 border rounded mb-4">
            <option value="">Select Program here</option>
            <?php if ($result2->num_rows > 0) { ?>
                <?php do { ?>
                    <option value="<?= $row2['program_name'] ?>">
                        <?= $row2['program_name'] ?>
                    </option>
                <?php } while ($row2 = $result2->fetch_assoc()) ?>
            <?php } ?>
        </select>

        <!-- Package -->
        <span>Package Number</span>

        <input type="text" name="package_number" class="w-full px-3 py-2 border rounded mb-4">

        <!-- Fee Inputs -->
        <?php
        $fees = [
            "Tuition Fee",
            "Misc Fee",
            "OJT Fee and Medical",
            "System Fee",
            "Assessment Fee",
            "Uniform (Scrub and Polo Drifit)",
            "ID",
            "Books",
            "Kit",
            "Demo1",
            "Demo2",
            "Demo3",
            "Demo4"
        ];
        ?>

        <?php foreach ($fees as $fee): ?>
            <div class="mb-2">
                <label class="block text-sm font-medium"><?= $fee ?></label>
                <input type="number" name="<?= strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $fee)) ?>"
                    class="fee-input w-full px-3 py-2 border rounded" placeholder="₱ 0.00" min="0" step="any"
                    <?= str_contains(strtolower($fee), 'demo') ? 'readonly' : '' ?>>
            </div>
        <?php endforeach; ?>

        <!-- Total Display -->
        <div class="mt-4 font-bold">
            Total: ₱ <span id="totalFee">0.00</span>
        </div>



        <div class="mb-2 border-3 border-dashed p-2 mt-2">
            <label class="block text-sm font-medium">Set Reservation Fee</label>
            <input type="number" id="" name="reservation_fee" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>

        <div class="flex flex-row">
            <!-- Submit Button -->
            <button type="button" id="saveTuitionBtn"
                class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 mx-2">
                Save Tuition Fee
            </button>
            <button type="button" id="closeTuitionBtn"
                class="mt-4 w-full bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 mx-2">
                Close
            </button>
        </div>
    </form>
</div>

<!-- Edit Program Modal -->
<div id="editTuitionModal"
    class="hidden fixed inset-0 bg-black/30 flex items-center justify-center z-50 transition-opacity duration-300">
    <form id="editTuitionForm" class="bg-white p-6 rounded-xl shadow-lg w-full max-w-md h-[80%] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Edit Tuition Fee</h2>
        <!-- Fee Inputs -->

        <input type="number" id="id_tuition" name="row_id" class="hidden ...">
        <!-- Program -->
        <!-- Program -->
        <span>Select Program Here</span>
        <select id="programSelect2" name="program" class="w-full px-3 py-2 border rounded mb-4">
            <option value="">Select Program here</option>
            <?php if ($result4->num_rows > 0) { ?>
                <?php do { ?>
                    <option value="<?= $row4['program_name'] ?>">
                        <?= $row4['program_name'] ?>
                    </option>
                <?php } while ($row4 = $result4->fetch_assoc()) ?>
            <?php } ?>
        </select>
        <span>Package Number</span>
        <select id="packageSelect2" name="package_number" class="w-full px-3 py-2 border rounded mb-4">
            <option value="">Select Package Number</option>
        </select>

        <!-- Fee Inputs -->
        <div class="mb-2">
            <label class="block text-sm font-medium">OJT Fee and Medical</label>
            <input type="number" id="edit_ojtmedical" name="ojtmedical" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Tuition Fee</label>
            <input type="number" id="edit_tuitionfee" name="tuitionfee" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Misc Fee</label>
            <input type="number" id="edit_miscfee" name="miscfee" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">System Fee</label>
            <input type="number" id="edit_systemfee" name="systemfee" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Assessment Fee</label>
            <input type="number" id="edit_assessmentfee" name="assessmentfee" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Uniform (Scrub and Polo Drifit)</label>
            <input type="number" id="edit_uniform" name="uniform" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">ID</label>
            <input type="number" id="edit_id" name="id" class="w-full px-3 py-2 border rounded" placeholder="₱ 0.00"
                min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Books</label>
            <input type="number" id="edit_books" name="books" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Kit</label>
            <input type="number" id="edit_kit" name="kit" class="w-full px-3 py-2 border rounded" placeholder="₱ 0.00"
                min="0" step="any">
        </div>

        <!-- Total Display -->
        <div class="mb-2">
            <label class="block text-sm font-medium">Demo1</label>
            <input type="number" id="edit_demo1" name="demo1" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Demo2</label>
            <input type="number" id="edit_demo2" name="demo2" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Demo3</label>
            <input type="number" id="edit_demo3" name="demo3" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>
        <div class="mb-2">
            <label class="block text-sm font-medium">Demo4</label>
            <input type="number" id="edit_demo4" name="demo4" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>



        <div class="mt-4 font-bold">
            Total: ₱ <span id="editTotalFee">0.00</span>
        </div>

        <div class="mb-2 border-3 border-dashed p-2 mt-2">
            <label class="block text-sm font-medium">Set Reservation Fee</label>
            <input type="number" id="reservationFee" name="reservation_fee" class="w-full px-3 py-2 border rounded"
                placeholder="₱ 0.00" min="0" step="any">
        </div>


        <div class="flex flex-row">
            <button type="submit"
                class="mt-4 w-full bg-yellow-600 text-white py-2 px-4 rounded hover:bg-yellow-700 mx-2">
                Update Tuition
            </button>
            <button type="button" onclick="closeModaleditTuition()"
                class="mt-4 w-full bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 mx-2">
                Close
            </button>
        </div>
    </form>
</div>




<!-- Edit Program Modal -->
<div id="deleteTuitionModal" class="fixed inset-0 hidden z-50 flex items-center justify-center bg-black/20">
    <div class=" bg-white p-6 rounded-xl w-full max-w-md">
        <h2 class="text-lg font-bold mb-4">Delete Tuition Entry</h2>
        <h1 class="text-2xl font-semibold italic" id="deleteProgramName"></h1>
        <form id="deleteTuitionForm">
            <input type="text" id="pprogram_management.php" class="w-full border px-3 py-2 rounded hidden" />


            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModalTuition2()"
                    class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Delete</button>
            </div>
        </form>
    </div>
</div>