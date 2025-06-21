document.addEventListener("DOMContentLoaded", ()=> {

    const sidebar = document.getElementById("sidebar");
    const toggle_sidebar = document.getElementById("toggleSidebar");
  
    let holder = 0;
 

    const logout = document.getElementById("logout");


    toggle_sidebar.addEventListener("click", ()=> {
        
        if(holder == 0){
            sidebar.classList.remove("hidden");
            console.log("open")
            toggle_sidebar.classList.add("ml-60")
            sidebar.classList.remove("w-[20%]")
             sidebar.classList.add("w-[40%]")
            holder  = 1;
        }else{
            sidebar.classList.add("hidden");
            console.log("close")
            toggle_sidebar.classList.remove("ml-60")
            sidebar.classList.add("w-[20%]")
            sidebar.classList.remove("w-[40%]")
            holder  = 0;
        }
    })


logout.addEventListener("click", () => {
    Swal.fire({
        template: "#my-template"
    }).then((result) => {
        if (result.isConfirmed) {
            // User clicked "Yes" - full logout
            performLogout();
        } else if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
            // User clicked "Switch Users"
            switchUsers();
        } else if (result.isDenied) {
            // User clicked "Cancel" - do nothing
            console.log("Logout cancelled");
        }
    });
});

// Function to perform full logout
function performLogout() {
    // Use AJAX to call logout.php without redirecting
    fetch('functions/logout.php?action=logout', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.ok) {
            window.location.href = '../index.php'; // Redirect to login page after successful logout
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        Swal.fire('Error', 'Failed to logout. Please try again.', 'error');
    });
}

// Function to switch users
function switchUsers() {
    // Use AJAX to call logout.php with switch parameter
    fetch('functions/logout.php?action=switch', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.ok) {
            window.location.href = '../index.php'; // Redirect to login page for new user
        }
    })
    .catch(error => {
        console.error('Switch user error:', error);
        Swal.fire('Error', 'Failed to switch users. Please try again.', 'error');
    });
}

     // Get DOM elements
  const showRegBtn = document.getElementById('showRegBtn');
  const modalOverlay = document.getElementById('modalOverlay');
  const closeModalBtn = document.getElementById('closeModalBtn');
  const closeModalBtn2 = document.getElementById('closeModalBtn2');
  
  // Function to open modal
  function openModal() {
    modalOverlay.classList.remove('hidden');
    document.body.classList.add('overflow-hidden'); // Prevent scrolling behind modal
  }
  
  // Function to close modal
  function closeModal() {
    modalOverlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }
  
  // Event listeners
  showRegBtn.addEventListener('click', openModal);
  closeModalBtn.addEventListener('click', closeModal);
  closeModalBtn2.addEventListener('click', closeModal);
  
  // Close modal when clicking outside the form (on the overlay)
  modalOverlay.addEventListener('click', function(event) {
    if (event.target === modalOverlay) {
      closeModal();
    }
  });
  
  // Close modal with Escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && !modalOverlay.classList.contains('hidden')) {
      closeModal();
    }
  });

})







        function generateStudentNumber() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            
            // Generate a random number between 1 and 9999
            const randomNum = Math.floor(Math.random() * 9999) + 1;
            const formattedRandom = String(randomNum).padStart(4, '0');
            
            const studentNumber = `${year}${month}${day}A${formattedRandom}`;
            document.getElementById('studentNumber').value = studentNumber;
        }
        
        function copyAddress() {
            if (document.getElementById('sameAsAbove').checked) {
                // Copy main address to emergency address
                document.getElementById('emergencyRegion').value = document.getElementById('region').value;
                document.getElementById('emergencyProvince').value = document.getElementById('province').value;
                document.getElementById('emergencyCity').value = document.getElementById('city').value;
                document.getElementById('emergencyBrgy').value = document.getElementById('brgy').value;
                document.getElementById('emergencyPurok').value = document.getElementById('purok').value;
                
                // Disable emergency address fields
                document.getElementById('emergencyRegion').disabled = true;
                document.getElementById('emergencyProvince').disabled = true;
                document.getElementById('emergencyCity').disabled = true;
                document.getElementById('emergencyBrgy').disabled = true;
                document.getElementById('emergencyPurok').disabled = true;
            } else {
                // Enable emergency address fields
                document.getElementById('emergencyRegion').disabled = false;
                document.getElementById('emergencyProvince').disabled = false;
                document.getElementById('emergencyCity').disabled = false;
                document.getElementById('emergencyBrgy').disabled = false;
                document.getElementById('emergencyPurok').disabled = false;
            }
        }
        
        // Generate student number on page load
        window.onload = function() {
            generateStudentNumber();
        };
 
        


        