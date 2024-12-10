<?php
session_name('instructor_session');
session_start();


if (!isset($_SESSION['instructor_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';


if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header("Location: dashboard.php");
    exit();
}

$course_id = $_GET['course_id'];
$instructor_id = $_SESSION['instructor_id'];


$course_check_query = "SELECT c.id FROM courses c 
                       JOIN course_instructors ci ON c.id = ci.course_id
                       WHERE c.id = ? AND ci.instructor_id = ?";
$stmt = $conn->prepare($course_check_query);
$stmt->bind_param("ii", $course_id, $instructor_id);
$stmt->execute();
$course_result = $stmt->get_result();

if ($course_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $due_date = $_POST['due_date'];
    $max_points = intval($_POST['max_points']);

  
    $errors = [];
    if (empty($title)) $errors[] = "Activity title is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($due_date)) $errors[] = "Due date is required.";
    if ($max_points <= 0) $errors[] = "Maximum points must be a positive number.";

   
    $mc_questions = [];
    if (isset($_POST['mc_question']) && is_array($_POST['mc_question'])) {
        foreach ($_POST['mc_question'] as $index => $question) {
            if (trim($question) !== '') {
                $mc_question = [
                    'question' => trim($question),
                    'options' => [],
                    'correct_answer' => $_POST['mc_correct_answer'][$index] ?? ''
                ];

               
                $valid_options = [];
                if (isset($_POST['mc_option'][$index]) && is_array($_POST['mc_option'][$index])) {
                    foreach ($_POST['mc_option'][$index] as $option) {
                        if (trim($option) !== '') {
                            $valid_options[] = trim($option);
                        }
                    }
                }

               
                if (count($valid_options) < 2) {
                    $errors[] = "Multiple choice question " . ($index + 1) . " must have at least 2 options.";
                }
                if (empty($mc_question['correct_answer'])) {
                    $errors[] = "Please select a correct answer for multiple choice question " . ($index + 1);
                }

                $mc_question['options'] = $valid_options;
                $mc_questions[] = $mc_question;
            }
        }
    }

    if (empty($errors)) {
       
        $conn->begin_transaction();

        try {
           
            $insert_query = "INSERT INTO course_activities 
                            (course_id, title, description, type, due_date, max_points, activity_name, activity_date, activity_time) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
           
            $activity_date = date('Y-m-d', strtotime($due_date));
            $activity_time = date('H:i:s', strtotime($due_date));
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param(
                "issssdsss", 
                $course_id, 
                $title, 
                $description, 
                $type, 
                $due_date, 
                $max_points, 
                $title,
                $activity_date, 
                $activity_time
            );

            if ($stmt->execute()) {
                $activity_id = $stmt->insert_id;

                
                if (!empty($mc_questions)) {
                    $mc_query = "INSERT INTO multiple_choice_questions 
                                (activity_id, question, options, correct_answer) 
                                VALUES (?, ?, ?, ?)";
                    $mc_stmt = $conn->prepare($mc_query);

                    foreach ($mc_questions as $mc_question) {
                        $options_json = json_encode($mc_question['options']);
                        $mc_stmt->bind_param(
                            "isss", 
                            $activity_id, 
                            $mc_question['question'], 
                            $options_json, 
                            $mc_question['correct_answer']
                        );
                        $mc_stmt->execute();
                    }
                }

        
                $conn->commit();

                $_SESSION['success_message'] = "Activity created successfully!";
                header("Location: course_resources.php?id=" . $course_id);
                exit();
            } else {
                $errors[] = "Failed to create activity. Please try again.";
            }
        } catch (Exception $e) {
           
            $conn->rollback();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course Activity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card-custom {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .mc-question-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card card-custom">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Course Activity</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="activityForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Activity Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                                    echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                                ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Activity Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="assignment">Assignment</option>
                                        <option value="quiz">Quiz</option>
                                        <option value="exam">Exam</option>
                                        <option value="project">Project</option>
                                        <option value="presentation">Presentation</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="due_date" class="form-label">Due Date and Time</label>
                                    <input type="datetime-local" class="form-control" id="due_date" name="due_date" required
                                           value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="max_points" class="form-label">Maximum Points</label>
                                <input type="number" class="form-control" id="max_points" name="max_points" 
                                       min="1" max="100" required
                                       value="<?php echo isset($_POST['max_points']) ? intval($_POST['max_points']) : 100; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Multiple Choice Questions (Optional)</label>
                                <div id="mc-questions-container">
                                  
                                </div>
                                <button type="button" id="add-mc-question" class="btn btn-secondary mt-2">
                                    <i class="bi bi-plus-circle me-2"></i>Add Multiple Choice Question
                                </button>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Create Activity
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mcQuestionsContainer = document.getElementById('mc-questions-container');
        const addMcQuestionBtn = document.getElementById('add-mc-question');
        const activityForm = document.getElementById('activityForm');
        let questionIndex = 0;

        addMcQuestionBtn.addEventListener('click', function() {
            const questionSection = document.createElement('div');
            questionSection.className = 'mc-question-section mb-3';
            questionSection.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Question</label>
                    <input type="text" class="form-control mc-question" name="mc_question[]" placeholder="Enter multiple choice question">
                </div>
                <div class="options-container">
                    <div class="mb-3">
                        <label class="form-label">Options</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control mc-option" name="mc_option[${questionIndex}][]" placeholder="Option 1">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0 mc-correct-answer" type="radio" name="mc_correct_answer[${questionIndex}]" value="0">
                            </div>
                        </div>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control mc-option" name="mc_option[${questionIndex}][]" placeholder="Option 2">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0 mc-correct-answer" type="radio" name="mc_correct_answer[${questionIndex}]" value="1">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-option">
                        <i class="bi bi-plus-circle me-2"></i>Add Option
                    </button>
                </div>
                <button type="button" class="btn btn-sm btn-danger remove-mc-question mt-2">
                    <i class="bi bi-trash me-2"></i>Remove Question
                </button>
            `;

            
            questionSection.querySelector('.add-option').addEventListener('click', function() {
                const optionsContainer = this.closest('.options-container');
                const optionIndex = optionsContainer.querySelectorAll('.input-group').length;
                
                const newOption = document.createElement('div');
                newOption.className = 'input-group mb-2';
                newOption.innerHTML = `
                    <input type="text" class="form-control mc-option" name="mc_option[${questionIndex}][]" placeholder="Option ${optionIndex + 1}">
                    <div class="input-group-text">
                        <input class="form-check-input mt-0 mc-correct-answer" type="radio" name="mc_correct_answer[${questionIndex}]" value="${optionIndex}">
                    </div>
                `;
                
                optionsContainer.querySelector('.mb-3').insertBefore(newOption, this);
            });

           
            questionSection.querySelector('.remove-mc-question').addEventListener('click', function() {
                questionSection.remove();
            });

            mcQuestionsContainer.appendChild(questionSection);
            questionIndex++;
        });

       
        activityForm.addEventListener('submit', function(event) {
            let isValid = true;

            
            const mcQuestionSections = document.querySelectorAll('.mc-question-section');
            mcQuestionSections.forEach((section, index) => {
                const question = section.querySelector('.mc-question');
                const options = section.querySelectorAll('.mc-option');
                const correctAnswers = section.querySelectorAll('.mc-correct-answer');

                
                question.classList.remove('option-error');
                options.forEach(option => option.classList.remove('option-error'));

                
                if (options[0].value.trim() !== '') {
                    if (question.value.trim() === '') {
                        question.classList.add('option-error');
                        isValid = false;
                    }

                   
                    const filledOptions = Array.from(options).filter(option => option.value.trim() !== '');
                    if (filledOptions.length < 2) {
                        filledOptions.forEach(option => option.classList.add('option-error'));
                        isValid = false;
                    }

                    
                    const selectedCorrectAnswer = Array.from(correctAnswers).find(radio => radio.checked);
                    if (!selectedCorrectAnswer) {
                        correctAnswers[0].closest('.input-group-text').classList.add('option-error');
                        isValid = false;
                    }
                }
            });

           
            if (!isValid) {
                event.preventDefault();
                alert('Please complete all multiple choice questions correctly.');
            }
        });
    });
    </script>
</body>
</html>