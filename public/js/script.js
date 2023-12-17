function openModal() {
    document.getElementById('myModal').style.display = 'block';
    document.getElementById('overlay').style.display = 'block';
}

// Function to close the modal
function closeModal() {
    document.getElementById('myModal').style.display = 'none';
    document.getElementById('overlay').style.display = 'none';
}

// Event listener for the link
document.addEventListener('DOMContentLoaded', function() {
document.getElementById('openModalLink').addEventListener('click', function(event) {
    event.preventDefault();
    openModal();
});
});

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

    $('.ban-put-btn').on('click', function () {
        var userid= $(this).data('post-id');
        var username= $(this).data('post-username');
        var password= $(this).data('post-password');
        var avatar= $(this).data('post-avatar');

        var confirmBan= confirm("Are you sure you want to ban this user?");
        if (!confirmBan) {
            return;
        }

        let obj= new Object();
        obj.id= userid;
        obj.username= username;
        obj.password= password;
        obj.avatar= avatar;
        obj.ban= true;

        var ajaxRequest= new XMLHttpRequest();
        ajaxRequest.open('PUT', '/ban');
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
    
    $('.unban-put-btn').on('click', function () {
        var userid= $(this).data('post-id');
        var username= $(this).data('post-username');
        var password= $(this).data('post-password');
        var avatar= $(this).data('post-avatar');

        var confirmBan= confirm("Are you sure you want to unban this user?");
        if (!confirmBan) {
            return;
        }

        let obj= new Object();
        obj.id= userid;
        obj.username= username;
        obj.password= password;
        obj.avatar= avatar;
        obj.ban= false;

        var ajaxRequest= new XMLHttpRequest();
        ajaxRequest.open('PUT', '/unban');
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

    $('.delete-comment-btn').on('click', function () {
        var CommentId= $(this).data('comment-id');
        var confirmDelete= confirm("Are you sure you want to delete this Commentaire?");
        if (!confirmDelete) {
            return;
        }

        let obj= new Object();
        obj.id= CommentId;

        var ajaxRequest= new XMLHttpRequest();
        ajaxRequest.open('DELETE', '/deletecomment');
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

    $('.delete-reponse-btn').on('click', function () {
        var ReponseId= $(this).data('reponse-id');
        var confirmDelete= confirm("Are you sure you want to delete this Reponse of Commentaire?");
        if (!confirmDelete) {
            return;
        }

        let obj= new Object();
        obj.id= ReponseId;

        var ajaxRequest= new XMLHttpRequest();
        ajaxRequest.open('DELETE', '/deletereponse');
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