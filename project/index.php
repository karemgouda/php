<?php
require_once 'config/Database.php';
require_once 'App/Models/Student.php';
require_once 'App/Controllers/StudentController.php';
require_once 'App/Models/Course.php';
require_once 'App/Controllers/CourseController.php';
require_once 'App/Models/TeachingAssistant.php';
require_once 'App/Controllers/TeachingAssistantController.php';
require_once 'App/Models/Section.php';
require_once 'App/Controllers/SectionController.php';
require_once 'App/Models/Attendance.php';
require_once 'App/Controllers/AttendanceController.php';
require_once 'App/Models/Security.php';
require_once 'App/Controllers/SecurityController.php';
require_once 'App/Models/StudentCourse.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = new Database();
    $studentController = new StudentController($db);
    $courseController = new CourseController($db);
    $teachingAssistantController = new TeachingAssistantController($db);
    $sectionController = new SectionController($db);
    $attendanceController = new AttendanceController($db);
    $securityController = new SecurityController($db);

    if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD'])) {
        $request = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($request) {
            case '/':
            case '':
                echo json_encode(["message" => "Welcome to the Student Management System API!"]);
                break;

            case '/student':
                handleStudentRequests($method, $studentController);
                break;

            case '/courses':
                handleCourseRequests($method, $courseController);
                break;

            case '/teaching-assistants':
                handleTeachingAssistantRequests($method, $teachingAssistantController);
                break;

            case '/students/details':
                if ($method == 'GET') {
                    $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
                    echo $studentController->getDetails($student_id);
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;

            case '/teaching-assistants/courses':
                if ($method == 'GET' && isset($_GET['ta_id'])) {
                    echo $teachingAssistantController->getAssignedCourses($_GET['ta_id']);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Bad Request: Missing or incorrect TA ID']);
                }
                break;

            case '/teaching-assistants/sections':
                if ($method == 'GET' && isset($_GET['ta_id'])) {
                    echo $teachingAssistantController->getSectionsForCourses($_GET['ta_id']);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Bad Request: Missing or incorrect TA ID']);
                }
                break;

            case '/sections':
                handleSectionRequests($method, $sectionController);
                break;

            case '/attendance':
                handleAttendanceRequests($method, $attendanceController);
                break;

            case '/attendance/students':
                handleStudentAttendanceRequests($method, $attendanceController);
                break;

            case '/security':
                handleSecurityRequests($method, $securityController);
                break;

            case '/login/student':
                if ($method == 'POST') {
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo $studentController->login($data['email'], $data['password']);
                }   
                break;

            case '/login/ta':
                if ($method == 'POST') {
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo $teachingAssistantController->login($data['email'], $data['password']);
                }
                break;

            case '/login/security':
                if ($method == 'POST') {
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo $securityController->login($data['email'], $data['password']);
                }
                break;

            case '/gate-entry':
                if ($method == 'POST') {
                    $data = json_decode(file_get_contents('php://input'), true);
                    echo $studentController->processQRcode($data['qr_code']);
                }
                break;

            case '/record-attendance':
                if ($method == 'POST') {
                    $data = json_decode(file_get_contents('php://input'), true);
                    $result = $attendanceController->recordAttendance(
                        $data['qr_code'],
                        $data['section_id'],
                        $data['course_id'],
                        $data['week_number'],
                        $data['absence_status'],
                        $data['timestamp']
                    );

                    if (is_array($result)) {
                        $result = json_encode($result);
                    }

                    echo $result;
                }
                break;

            default:
                http_response_code(404);
                echo json_encode(['error' => 'Not Found Or Wrong Request']);
                break;
        }
    } else {
        echo json_encode(["message" => "This script should be accessed via an HTTP request."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleStudentRequests($method, $studentController) {
    switch ($method) {
        case 'GET':
            if (isset($_GET['student_id'])) {
                echo json_encode($studentController->show($_GET['student_id']));
            } else {
                echo json_encode($studentController->index());
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($studentController->store($data));
            break;
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($studentController->update($_GET['id'], $data));
            break;
        case 'DELETE':
            echo json_encode($studentController->destroy($_GET['id']));
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleCourseRequests($method, $courseController) {
    switch ($method) {
        case 'GET':
            if (isset($_GET['course_id'])) {
                echo json_encode($courseController->show($_GET['course_id']));
            } else {
                echo json_encode($courseController->index());
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($courseController->store($data));
            break;
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($courseController->update($_GET['course_id'], $data));
            break;
        case 'DELETE':
            echo json_encode($courseController->destroy($_GET['id']));
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleTeachingAssistantRequests($method, $teachingAssistantController) {
    switch ($method) {
        case 'GET':
            if (isset($_GET['ta_id'])) {
                echo json_encode($teachingAssistantController->show($_GET['ta_id']));
            } else {
                echo json_encode($teachingAssistantController->index());
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($teachingAssistantController->store($data));
            break;
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($teachingAssistantController->update($_GET['ta_id'], $data));
            break;
        case 'DELETE':
            echo json_encode($teachingAssistantController->destroy($_GET['course_id']));
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleSectionRequests($method, $sectionController) {
    switch ($method) {
        case 'GET':
            if (isset($_GET['section_id'])) {
                echo json_encode($sectionController->show($_GET['section_id']));
            } else {
                echo json_encode($sectionController->index());
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($sectionController->store($data));
            break;
        case 'PUT':
            parse_str(file_get_contents("php://input"), $_PUT);
            if (isset($_GET['section_id'])) {
                echo json_encode($sectionController->update($_GET['section_id'], $_PUT));
            }
            break;
        case 'DELETE':
            if (isset($_GET['section_id'])) {
                echo json_encode($sectionController->destroy($_GET['section_id']));
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleAttendanceRequests($method, $attendanceController) {
    switch ($method) {
        case 'GET':
            if (isset($_GET['attendance_id'])) {
                echo json_encode($attendanceController->show($_GET['attendance_id']));
            } else {
                echo json_encode($attendanceController->index());
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($attendanceController->store($data));
            break;
        case 'PUT':
            parse_str(file_get_contents("php://input"), $_POST);
            if (isset($_GET['attendance_id'])) {
                echo json_encode($attendanceController->update($_GET['attendance_id'], $_POST));
            }
            break;
        case 'DELETE':
            if (isset($_GET['attendance_id'])) {
                echo json_encode($attendanceController->destroy($_GET['attendance_id']));
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleStudentAttendanceRequests($method, $attendanceController) {
    if ($method == 'GET') {
        if (isset($_GET['section_group_number']) && isset($_GET['week_number'])) {
            echo json_encode($attendanceController->getStudentsAttendance($_GET['section_group_number'], $_GET['week_number']));
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request: Missing parameters']);
        }
    } elseif ($method == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['section_group_number']) && isset($data['week_number'])) {
            echo json_encode($attendanceController->getStudentsAttendance($data['section_group_number'], $data['week_number']));
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request: Missing parameters in request body']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Bad Request: Method not allowed']);
    }
}

function handleSecurityRequests($method, $securityController) {
    switch ($method) {
        case 'GET':
            if (isset($_GET['security_id'])) {
                echo json_encode($securityController->show($_GET['security_id']));
            } else {
                echo json_encode($securityController->index());
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($securityController->store($data));
            break;
        case 'PUT':
            parse_str(file_get_contents("php://input"), $_POST);
            if (isset($_GET['security_id'])) {
                echo json_encode($securityController->update($_GET['security_id'], $_POST));
            }
            break;
        case 'DELETE':
            if (isset($_GET['security_id'])) {
                echo json_encode($securityController->destroy($_GET['security_id']));
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}
