<template id="my-template">
    <swal-title>
        Are you sure you want to logout?
    </swal-title>
    <swal-icon type="warning" color="red"></swal-icon>
    <swal-button type="confirm">
        Yes
    </swal-button>

    <swal-button type="deny">
        Cancel
    </swal-button>
    <swal-param name="allowEscapeKey" value="false" />
    <swal-param name="customClass" value='{ "popup": "my-popup" }' />
    <swal-function-param name="didOpen" value="popup => console.log(popup)" />
</template>