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



function openModal2(myVariable, userid, postid,  islock) {
    document.getElementById("postid").value= postid;
    document.getElementById("Inputhide").value= userid;
    document.getElementById("myInputhidden").value= islock;
    document.getElementById("myInput").value = myVariable;
    document.getElementById('myModal2').style.display = 'block';
    document.getElementById('overlay2').style.display = 'block'; 
}

// Function to close the modal
function closeModal2() {
    document.getElementById('myModal2').style.display = 'none';
    document.getElementById('overlay2').style.display = 'none';
    document.getElementById("myInput").value ="";
}


function openModalcomment(content, id) {
    document.getElementById("commentid").value= id;
    document.getElementById("in").value= content;
    document.getElementById('myModalcomment').style.display= 'block';
    document.getElementById('overlaycomment').style.display= 'block';
}

// Function to close the modal
function closeModalcomment() {
    document.getElementById('myModalcomment').style.display= 'none';
    document.getElementById('overlaycomment').style.display= 'none';
    document.getElementById("in").value =""; 
}


function openModalreponse(content, id) {
    document.getElementById("incontent").value= content;
    document.getElementById("reponseid").value= id;
    document.getElementById('myModalreponse').style.display= 'block';
    document.getElementById('overlayreponse').style.display= 'block';
}

// Function to close the modal
function closeModalreponse() {
    document.getElementById('myModalreponse').style.display= 'none';
    document.getElementById('overlayreponse').style.display= 'none';
    document.getElementById("in").value =""; 
}


function openModalProfil() {
    document.getElementById('myModalProfil').style.display= 'block';
    document.getElementById('overlayProfil').style.display= 'block'; 
}

// Function to close the modal
function closeModalProfil() {
    document.getElementById('myModalProfil').style.display= 'none';
    document.getElementById('overlayProfil').style.display= 'none';
}


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