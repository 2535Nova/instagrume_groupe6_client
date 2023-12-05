$(document).ready(function () {
    $('.delete-post-btn').on('click', function () {
        var postId= $(this).data('post-id');
        var confirmDelete= confirm("Are you sure you want to delete this post?");
        if (!confirmDelete) {
            return;
        }

        let obj= new Object();
        obj.id= postId;

        var ajaxRequest= new XMLHttpRequest();
        ajaxRequest.open('DELETE', '/deletepost');
        ajaxRequest.send(JSON.stringify(obj));

        ajaxRequest.onreadystatechange = function() {
            if(ajaxRequest.readyState === 4) {
                if(ajaxRequest.status === 200) {
                    console.log(ajaxRequest.responseText);
                    location.reload();
                }
                else {
                    console.log("Status error: " + ajaxRequest.status);
                }
            }
        };
    });
});