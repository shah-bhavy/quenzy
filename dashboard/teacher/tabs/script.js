        // Open Edit Modal
        function openEditModal(testId, testName) {
            document.getElementById('test_id').value = testId;
            document.getElementById('edit_test_name').value = testName;
            document.getElementById('editModal').style.display = 'flex';
        }

        // Close Edit Modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        function fetchChapters(subjectId) {
    if (!subjectId) {
        document.getElementById('chapters_table').style.display = 'none';
        return;
    }

    fetch(`fetch_chapters.php?subject_id=${subjectId}`)
        .then((response) => response.json())
        .then((chapters) => {
            const chapterRows = chapters
                .map(
                    (chapter) => `
                    <tr>
                        <td>${chapter.chapter_name}, ${chapter.chapter_id}</td>
                        <td>
                            <input type="number" name="weightages[${chapter.chapter_id}]" min="0" max="100" placeholder="Weightage" required>
                        </td>
                    </tr>`
                )
                .join('');
            document.getElementById('chapter_rows').innerHTML = chapterRows;
            document.getElementById('chapters_table').style.display = 'block';
        })
        .catch((error) => console.error('Error fetching chapters:', error));
}
// Open Test Modal
function openTestModal(subjectId, subjectName) {
    const modal = document.getElementById('testModal');
    modal.style.display = 'flex';
}

// Close Test Modal
function closeTestModal() {
    const modal = document.getElementById('testModal');
    modal.style.display = 'none';
}
function closeQuestionModal() {
    const modal = document.getElementById('questionModel');
    modal.style.display = 'none';
}
function openQuestionModal() {
    document.getElementById('testModal').style.display = 'none';
    
    document.getElementById('questionModal').style.display = 'flex';
}

function closeQuestionModal() {
    document.getElementById('questionModal').style.display = 'none';
}
    // Fetch Chapters Function
    function fetchChapters(subjectId) {
        if (subjectId === "") {
            document.getElementById("chapters_table").style.display = "none";
            document.getElementById("chapter_rows").innerHTML = "";
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "fetch_chapters.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function () {
            if (xhr.status === 200) {
                document.getElementById("chapters_table").style.display = "block";
                document.getElementById("chapter_rows").innerHTML = xhr.responseText;
            }
        };
        xhr.send("subject_id=" + encodeURIComponent(subjectId));
    }

    // Validate Test Form
    function validateTestForm() {
        const subjectSelect = document.getElementById("subject_select");
        const chapterRows = document.querySelectorAll("#chapter_rows input[type='number']");
        let totalWeightage = 0;

        // Validate Subject Selection
        if (subjectSelect.value === "") {
            document.getElementById("subjectError").style.display = "block";
            return false;
        } else {
            document.getElementById("subjectError").style.display = "none";
        }

        // Validate Weightages
        for (let input of chapterRows) {
            if (input.value === "" || isNaN(input.value) || input.value < 0) {
                input.classList.add("error");
                return false;
            } else {
                input.classList.remove("error");
                totalWeightage += parseFloat(input.value);
            }
        }

        if (totalWeightage !== 100) {
            document.getElementById("weightageError").style.display = "block";
            return false;
        } else {
            document.getElementById("weightageError").style.display = "none";
        }

        return true;
    }

    // Open Next Modal
    function openQuestionModal() {
        if (validateTestForm()) {
            document.getElementById('testModal').style.display = 'none';
            document.getElementById('questionModal').style.display = 'flex';
        }
    }

    // Close Modal
    function closeTestModal() {
        const modal = document.getElementById("testModal");
        modal.style.display = "none"; // Hide the modal
    }