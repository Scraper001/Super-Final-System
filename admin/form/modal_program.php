<!--  Add Program Modal -->
<div id="editProgramModal" class="fixed inset-0 bg-black/20 flex items-center justify-center hidden z-50">
    <form id="editProgramForm" method="POST" class="bg-white p-6 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Edit Program</h2>

        <!-- Program Name -->
        <label class="block mb-2 font-semibold" for="programName">Program Name</label>
        <input type="text" name="program_name" id="editProgramName" class="w-full border-2 rounded-lg p-2 mb-4"
            placeholder="Enter program name" required>

        <!-- Trainor ID Number -->
        <label class="block mb-2 font-semibold" for="editTrainorId">NTTC</label>
        <input type="text" name="trainor_id" id="editTrainorId" class="w-full border-2 rounded-lg p-2 mb-4"
            placeholder="Enter trainor ID number" required>



        <!-- Class Schedule -->
        <label class="block mb-2 font-semibold">Class Schedule</label>
        <div class="flex items-center mb-2 gap-2">
            <select id="editClassScheduleSelect" class="flex-1 border-2 rounded-lg p-2">
                <optgroup label="Priority Schedule of Training">
                    <option value="Weekday 1">Weekday 1 </option>
                    <option value="Weekday 2">Weekday 2 </option>
                    <option value="Weekday 3">Weekday 3 </option>
                    <option value="Weekday 4">Weekday 4 </option>
                </optgroup>
            </select>

            <button type="button" onclick="addSchedule2()"
                class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700">Add</button>
        </div>
        <ul id="editscheduleList" class="list-disc pl-5 text-sm text-gray-700 mb-4"></ul>

        <label class="block mb-2 font-semibold">Opening Class</label>
        <div class="flex items-center mb-2 gap-2">
            <input type="date" id="editOpeningClasses" name="OpeningClasses"
                class="w-full border-2 rounded-lg p-2 mb-4">
        </div>
        <label class="block mb-2 font-semibold">Closing Class</label>
        <div class="flex items-center mb-2 gap-2">
            <input type="date" id="editClosingClasses" name="ClosingClasses"
                class="w-full border-2 rounded-lg p-2 mb-4">
        </div>



        <!-- Hidden input field to store schedules -->
        <input type="hidden" name="class_schedule" id="editclassScheduleInput">


        <!-- Buttons -->
        <div class="flex justify-end space-x-2">
            <button type="button" class="bg-gray-300 hover:bg-gray-400 text-black px-4 py-2 rounded-lg"
                onclick="closeModalProgramEdit()">Cancel</button>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">Save</button>
        </div>
    </form>
</div>


<!-- Edit Program Modal -->
<div id="deleteProgramModal" class="fixed inset-0 hidden z-50 flex items-center justify-center bg-black/20">
    <div class=" bg-white p-6 rounded-xl w-full max-w-md">
        <h2 class="text-lg font-bold mb-4">Delete Program</h2>
        <h1 class="text-2xl font-semibold italic" id="deleteProgramName"></h1>
        <form id="deleteProgramForm">
            <input type="text" id="deleteProgramID" class="w-full border px-3 py-2 rounded hidden" />


            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal2()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Delete</button>
            </div>
        </form>
    </div>
</div>