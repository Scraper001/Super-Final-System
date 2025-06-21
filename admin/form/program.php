<div id="programModal" class="fixed inset-0 bg-black/20 flex items-center justify-center hidden z-50">
    <form id="addProgramForm" method="POST"
        class="bg-white p-6 rounded-xl shadow-lg w-full max-w-5xl max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Add Program</h2>

        <!-- Program Name -->
        <label class="block mb-2 font-semibold" for="programName">Program Name</label>
        <input type="text" name="program_name" id="programName" class="w-full border-2 rounded-lg p-2 mb-4"
            placeholder="Enter program name" required>

        <!-- Trainor ID Number -->
        <label class="block mb-2 font-semibold" for="trainorId">NTTC</label>
        <input type="text" name="trainor_id" id="trainorId" class="w-full border-2 rounded-lg p-2 mb-4"
            placeholder="Enter trainor ID number" required>

        <!-- Class Schedule Table -->
        <label class="block mb-2 font-semibold">Class Schedule</label>

        <!-- Add Schedule Form -->
        <div class="bg-gray-50 p-4 rounded-lg mb-4 border">
            <h3 class="font-semibold mb-3">Add Training Schedule</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Week</label>
                    <input type="text" id="scheduleWeek" class="w-full border rounded-lg p-2"
                        placeholder="e.g., Week 1">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Description</label>
                    <input type="text" id="scheduleDescription" class="w-full border rounded-lg p-2"
                        placeholder="Training description">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Training Date</label>
                    <input type="date" id="scheduleDate" class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Start Time</label>
                    <input type="time" id="scheduleStartTime" class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">End Time</label>
                    <input type="time" id="scheduleEndTime" class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Day of Week</label>
                    <select id="scheduleDayOfWeek" class="w-full border rounded-lg p-2">
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
            </div>
            <button type="button" onclick="addSchedule()"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Add Schedule
            </button>
        </div>

        <!-- Schedule Table -->
        <div class="overflow-x-auto mb-4">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-3 py-2 text-left text-sm">Week</th>
                        <th class="border border-gray-300 px-3 py-2 text-left text-sm">Description</th>
                        <th class="border border-gray-300 px-3 py-2 text-left text-sm">Training Date</th>
                        <th class="border border-gray-300 px-3 py-2 text-left text-sm">Start Time</th>
                        <th class="border border-gray-300 px-3 py-2 text-left text-sm">End Time</th>
                        <th class="border border-gray-300 px-3 py-2 text-left text-sm">Day of Week</th>
                        <th class="border border-gray-300 px-3 py-2 text-center text-sm">Actions</th>
                    </tr>
                </thead>
                <tbody id="scheduleTableBody">
                    <!-- Schedule rows will be added here dynamically -->
                </tbody>
            </table>
            <div id="noScheduleMessage" class="text-center text-gray-500 py-4 text-sm">No schedules added yet. Use
                the form above to add training schedules.</div>
        </div>

        <!-- Opening and Closing Class -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block mb-2 font-semibold">Opening Class</label>
                <input type="date" id="OpeningClasses" name="OpeningClasses" class="w-full border-2 rounded-lg p-2">
            </div>
            <div>
                <label class="block mb-2 font-semibold">Closing Class</label>
                <input type="date" id="ClosingClasses" name="ClosingClasses" class="w-full border-2 rounded-lg p-2">
            </div>
        </div>

        <!-- Hidden input field to store schedules -->
        <input type="hidden" name="class_schedule" id="classScheduleInput">

        <!-- Buttons -->
        <div class="flex justify-end space-x-2">
            <button type="button" class="bg-gray-300 hover:bg-gray-400 text-black px-4 py-2 rounded-lg"
                onclick="closeModalProgramAdd()">Cancel</button>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">Save</button>
        </div>
    </form>
</div>