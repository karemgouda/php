<?php

require_once __DIR__ . '/../Models/Student.php';
require_once __DIR__ . '/../Models/StudentCourse.php';

class StudentController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db->getConnection();
    }

    // All Students
    public function index()
    {
        try {
            $students = Student::getAll($this->db);
            return json_encode(['success' => true, 'data' => $students]);
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Get Student by ID
    public function show($id)
    {
        try {
            $student = Student::getById($this->db, $id);
            if ($student) {
                return json_encode(['success' => true, 'data' => $student]);
            } else {
                return json_encode(['success' => false, 'error' => 'Student not found']);
            }
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function login($email, $password)
    {
        try {
            // Authenticate user using the provided username and password
            $student = Student::login($this->db, $email, $password);
            return $student;
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    // Enroll a student in a course
    public function enroll($data)
    {
        try {
            // Ensure required data is provided
            if (!isset($data['student_id']) || !isset($data['course_id']) || !isset($data['semester'])) {
                throw new Exception("Student ID, Course ID, and Semester are required");
            }

            // Create new StudentCourse instance
            $studentCourse = new StudentCourse($this->db, null, $data['student_id'], $data['course_id'], $data['semester']);
            $result = $studentCourse->store();
            return $result;
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Disenroll a student from a course
    public function disenroll($student_course_id)
    {
        try {
            $studentCourse = new StudentCourse($this->db, $student_course_id);
            $result = $studentCourse->destroy();
            return $result;
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Create New Student
    public function store($data)
    {
        try {
            // Check if the required keys exist in the $data array
            if (
                !isset($data['name']) || !isset($data['email']) || !isset($data['faculty']) ||
                !isset($data['level']) || !isset($data['qr_code']) || !isset($data['section_group_number'])
            ) {
                throw new Exception("Required fields are missing");
            }

            // Access array keys after verifying their existence
            $name = $data['name'];
            $email = $data['email'];
            $faculty = $data['faculty'];
            $level = $data['level'];
            $qrCode = $data['qr_code'];
            $sectionGroupNumber = $data['section_group_number'];

            // Now you can proceed with your logic, such as creating a new Student instance and saving it to the database
            $student = new Student($this->db, null, $name, $email, null, $faculty, $level, $qrCode, $sectionGroupNumber);
            $student->save();

            return json_encode(['success' => true, 'message' => 'Student record created successfully']);
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    //Update Student By ID
    public function update($id, $data)
    {
        try {
            $student = Student::getById($this->db, $id);
            if ($student) {
                if (
                    !isset($data['name']) || !isset($data['email']) || !isset($data['faculty']) ||
                    !isset($data['level']) || !isset($data['qr_code']) || !isset($data['section_group_number'])
                ) {
                    throw new Exception("Required fields are missing");
                }

                $student->setName($data['name']);
                $student->setEmail($data['email']);
                $student->setFaculty($data['faculty']);
                $student->setLevel($data['level']);
                $student->setQrCode($data['qr_code']);
                $student->setSectionGroupNumber($data['section_group_number']);
                $student->save();
                return json_encode(['success' => true, 'message' => 'Student updated successfully']);
            } else {
                return json_encode(['success' => false, 'error' => 'Student not found']);
            }
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Delete Student By ID 
    public function destroy($id)
    {
        try {
            $student = Student::getById($this->db, $id);
            if ($student) {
                $student->delete();
                return json_encode(['success' => true, 'message' => 'Student deleted successfully']);
            } else {
                return json_encode(['success' => false, 'error' => 'Student not found']);
            }
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getDetails($student_id)
    {
        try {
            
            if (!$student_id) {
                throw new Exception("Student ID is required");
            }

            $student = Student::getById($this->db, $student_id);

            if ($student) {
                
                $details = $student->getDetails();
                return json_encode(['success' => true, 'data' => $details]);
            } else {
                return json_encode(['success' => false, 'error' => 'Student not found']);
            }
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    // Function To Generate QR Code of Student


    // Fucntion to confirm Gate Entry 
    public function processQRCode($qrCode)
    {
        try {
            if (!$qrCode) {
                throw new Exception("QR code data is required");
            }

            //$qrCode = $_POST['qr_code'];

            // Get student information based on the QR code
            $student = Student::getByQRCode($this->db, $qrCode);

            if (!$student) {
                throw new Exception("Student not found");
            }

            // Return the student information as a JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'student_id' => $student->getId(),
                'name' => $student->getName(),
                'faculty' => $student->getFaculty(),
                'level' => $student->getLevel(),
                'image' => $student->getimage()

            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
