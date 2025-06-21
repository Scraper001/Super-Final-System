<?php if ($open == false) { ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const typeRadios = document.querySelectorAll('input[name="type_of_payment"]');
            const totalPayment = document.getElementById("totalPayment");
            const cashToPay = document.getElementById("cashToPay");
            const cash = document.getElementById("cash");
            const change = document.getElementById("change");
            const demoSelection = document.getElementById("demoSelection");

            // Use PHP to assign payment values - Updated with new CDM calculation
            const demoPaymentAmount = <?php echo $CDM ?? 0; ?>; // Use calculated CDM instead of fixed demo fee
            const fullPaymentAmount = <?php echo $row_transaction1['balance'] ?? 0; ?>;
            const initialPaymentDisplay = <?php echo $adjusted_IP ?? $IP ?? 0; ?>; // Show adjusted IP if reservation exists
            const reservationAmount = <?php echo $R ?? 0; ?>;

            // Show payment info in console for debugging
            console.log('Demo Payment (CDM):', demoPaymentAmount);
            console.log('Full Payment:', fullPaymentAmount);
            console.log('Initial Payment (adjusted):', initialPaymentDisplay);
            console.log('Reservation:', reservationAmount);

            // When user selects payment type
            typeRadios.forEach((radio) => {
                radio.addEventListener("change", function () {
                    const value = this.value;

                    // Show or hide demo selection dropdown
                    demoSelection.style.display = value === "demo_payment" ? "block" : "none";

                    // Set payment fields based on calculation
                    if (value === "demo_payment") {
                        totalPayment.value = demoPaymentAmount.toFixed(2);
                        cashToPay.value = demoPaymentAmount.toFixed(2);
                    } else if (value === "full_payment") {
                        totalPayment.value = fullPaymentAmount;
                        cashToPay.value = fullPaymentAmount;
                    } else if (value === "initial_payment") {
                        // Show the adjusted initial payment if reservation exists
                        totalPayment.value = initialPaymentDisplay.toFixed(2);
                        cashToPay.value = initialPaymentDisplay.toFixed(2);
                    } else {
                        totalPayment.value = "";
                        cashToPay.value = "";
                    }

                    // Clear cash input and change
                    cash.value = "";
                    change.value = "";
                });
            });

            // Recalculate change
            cash.addEventListener("input", function () {
                const cashValue = parseFloat(cash.value);
                const cashToPayValue = parseFloat(cashToPay.value);

                if (!isNaN(cashValue) && !isNaN(cashToPayValue)) {
                    const changeValue = cashValue - cashToPayValue;
                    change.value = changeValue >= 0 ? changeValue.toFixed(2) : 0;
                } else {
                    change.value = "";
                }
            });

            // Update demo select to show calculated amount
            const demoSelect = document.getElementById("demoSelect");
            if (demoSelect) {
                demoSelect.addEventListener("change", function () {
                    if (this.value !== "") {
                        // All demos now have the same calculated amount
                        totalPayment.value = demoPaymentAmount.toFixed(2);
                        cashToPay.value = demoPaymentAmount.toFixed(2);
                    }
                });
            }
        });
    </script>


<?php } ?>