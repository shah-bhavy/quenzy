document.addEventListener('DOMContentLoaded', function () {
    var editModal = document.getElementById("editChapterModal");
    var uploadModal = document.getElementById("uploadModal");
    var closeEditBtn = document.querySelector("#editChapterModal .close");
    var closeUploadBtn = document.querySelector("#uploadModal .close");
    var dropArea = document.querySelector(".drop-area");
    var fileInput = document.getElementById("fileInput");
    var uploadForm = document.getElementById("uploadForm");
    var uploadProgress = document.getElementById("uploadProgress");
    var statusText = document.getElementById("statusText");

    // Open Edit Modal
    function openEditModal(chapterId, chapterName) {
        editModal.style.display = 'flex';
        document.getElementById('chapter_id').value = chapterId;
        document.getElementById('chapter_name').value = chapterName;
    }

    // Close Edit Modal
    function closeEditModal() {
        editModal.style.display = 'none';
    }

    // Open Upload Modal
    function openUploadModal(chapterId) {
        uploadModal.style.display = 'flex';
        document.getElementById('upload_chapter_id').value = chapterId;
    }

    // Close Upload Modal
    function closeUploadModal() {
        uploadModal.style.display = 'none';
    }

    // Handle Drag & Drop
    dropArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function (event) {
        var files = event.target.files;
        handleFiles(files);
    });

    dropArea.addEventListener('dragover', function (event) {
        event.preventDefault();
        dropArea.classList.add('active');
    });

    dropArea.addEventListener('dragleave', function () {
        dropArea.classList.remove('active');
    });

    dropArea.addEventListener('drop', function (event) {
        event.preventDefault();
        dropArea.classList.remove('active');
        var files = event.dataTransfer.files;
        handleFiles(files);
    });

    function handleFiles(files) {
        if (files.length) {
            var file = files[0];
            if (file.type !== "application/pdf") {
                alert("Only PDF files are allowed!");
                return;
            }
            if (file.size > 10485760) {
                alert("PDF file size cannot exceed 10 MB!");
                return;
            }
            uploadFile(file);
        }
    }

    function uploadFile(file) {
        var formData = new FormData(uploadForm);
        formData.append("file", file);

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "upload.php", true);

        xhr.upload.addEventListener("progress", function (event) {
            if (event.lengthComputable) {
                var percentComplete = (event.loaded / event.total) * 100;
                uploadProgress.value = percentComplete;
                statusText.textContent = `Uploading: ${Math.round(percentComplete)}%`;
            }
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        statusText.textContent = "Generating Questions...";
                        setTimeout(() => {
                            statusText.textContent = "Saving Questions...";
                        }, 1000);
                        setTimeout(() => {
                            statusText.textContent = "Updating Database...";
                        }, 2000);
                        setTimeout(() => {
                            statusText.textContent = "Done! You're ready to go.";
                            uploadModal.style.display = "none";
                            location.reload(); // Reload to update view
                        }, 3000);
                    } else {
                        statusText.textContent = "Error: " + response.message;
                    }
                } else {
                    statusText.textContent = "Error uploading file.";
                }
            }
        };

        xhr.send(formData);
    }

    closeEditBtn.onclick = closeEditModal;
    closeUploadBtn.onclick = closeUploadModal;
    window.onclick = function (event) {
        if (event.target === editModal) {
            closeEditModal();
        }
        if (event.target === uploadModal) {
            closeUploadModal();
        }
    };
});
