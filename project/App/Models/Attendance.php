<?php

require_once __DIR__ . '/../../config/Database.php';

class Attendance
{
    // Properties
    private $id;
    private $studentId;
    private $sectionId;
    private $courseId;
    private $weekNumber;
    private $sectionGroupNumber;
    private $absenceStatus;
    private $timestamp;

    private $db; // Database connection instance

    // Constructor
    public function __construct($db, $id = null, $studentId = '', $sectionId = '', $courseId = '', $weekNumber = '', $sectionGroupNumber = '', $absenceStatus = '', $timestamp = '')
    {
        $this->db = $db;

        if (!is_numeric($studentId) || !is_numeric($sectionId) || !is_numeric($courseId) || !is_numeric($weekNumber) || !is_numeric($sectionGroupNumber)) {
            throw new InvalidArgumentException("Invalid student ID, section ID, course ID, week number, or section group number");
        }

        $this->id = $id;
        $this->studentId = $studentId;
        $this->sectionId = $sectionId;
        $this->courseId = $courseId;
        $this->weekNumber = $weekNumber;
        $this->sectionGroupNumber = $sectionGroupNumber;
        $this->absenceStatus = $absenceStatus;
        $this->timestamp = $timestamp;
    }
    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getStudentId()
    {
        return $this->studentId;
    }

    public function getSectionId()
    {
        return $this->sectionId;
    }

    public function getCourseId()
    {
        return $this->courseId;
    }

    public function getWeekNumber()
    {
        return $this->weekNumber;
    }

    public function getSectionGroupNumber()
    {
        return $this->sectionGroupNumber;
    }

    public function getAbsenceStatus()
    {
        return $this->absenceStatus;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    // Setters
    public function setStudentId($studentId)
    {
        $this->studentId = $studentId;
    }

    public function setSectionId($sectionId)
    {
        $this->sectionId = $sectionId;
    }

    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;
    }

    public function setWeekNumber($weekNumber)
    {
        $this->weekNumber = $weekNumber;
    }

    public function setSectionGroupNumber($sectionGroupNumber)
    {
        $this->sectionGroupNumber = $sectionGroupNumber;
    }

    public function setAbsenceStatus($absenceStatus)
    {
        $this->absenceStatus = $absenceStatus;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    // Get attendance by ID
    public static function getById($db, $id)
    {
        $stmt = $db->prepare("SELECT * FROM attendance WHERE attendance_id = :attendance_id");
        $stmt->execute(['attendance_id' => $id]);

        $attendanceData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($attendanceData) {
            return new Attendance(
                $db,
                $attendanceData['attendance_id'],
                $attendanceData['student_id'],
                $attendanceData['section_id'],
                $attendanceData['course_id'],
                $attendanceData['week_number'],
                $attendanceData['section_group_number'],
                $attendanceData['absence_status'],
                $attendanceData['timestamp']
            );
        } else {
            return null; // Attendance not found
        }
    }

    // Get all attendance records
    public static function getAll($db)
    {
        $stmt = $db->query("SELECT * FROM attendance");

        $attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attendance = [];

        foreach ($attendanceData as $data) {
            $attendance[] = new Attendance(
                $db,
                $data['attendance_id'],
                $data['student_id'],
                $data['section_id'],
                $data['course_id'],
                $data['week_number'],
                $data['section_group_number'],
                $data['absence_status'],
                $data['timestamp']
            );
        }

        return $attendance;
    }

    // Save attendance record to the database
    public function save()
    {
        if ($this->id) {
            $stmt = $this->db->prepare("UPDATE attendance SET student_id = :student_id, section_id = :section_id, course_id = :course_id, week_number = :week_number, section_group_number = :section_group_number, absence_status = :absence_status, timestamp = :timestamp WHERE attendance_id = :attendance_id");
            $stmt->execute([
                'attendance_id' => $this->id,
                'student_id' => $this->studentId,
                'section_id' => $this->sectionId,
                'course_id' => $this->courseId,
                'week_number' => $this->weekNumber,
                'section_group_number' => $this->sectionGroupNumber,
                'absence_status' => $this->absenceStatus,
                'timestamp' => $this->timestamp
            ]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO attendance (student_id, section_id, course_id, week_number, section_group_number, absence_status, timestamp) VALUES (:student_id, :section_id, :course_id, :week_number, :section_group_number, :absence_status, :timestamp)");
            $stmt->execute([
                'student_id' => $this->studentId,
                'section_id' => $this->sectionId,
                'course_id' => $this->courseId,
                'week_number' => $this->weekNumber,
                'section_group_number' => $this->sectionGroupNumber,
                'absence_status' => $this->absenceStatus,
                'timestamp' => $this->timestamp
            ]);
            $this->id = $this->db->lastInsertId();
        }
    }

    // Delete attendance record from the database
    public function delete()
    {
        if ($this->id) {
            $stmt = $this->db->prepare("DELETE FROM attendance WHERE attendance_id = :attendance_id");
            $stmt->execute(['attendance_id' => $this->id]);
        }
    }
    public function getStudentsAttendance($sectionGroupNumber, $weekNumber)
    {
        try {
            // Prepare SQL query to fetch attendance data
            $sql = "SELECT s.student_id, s.name, a.absence_status
                FROM attendance a
                JOIN student s ON a.student_id = s.student_id
                WHERE a.section_group_number = :section_group_number
                AND a.week_number = :week_number";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':section_group_number', $sectionGroupNumber, PDO::PARAM_INT);
            $stmt->bindParam(':week_number', $weekNumber, PDO::PARAM_INT);
            $stmt->execute();

            // Fetch attendance data
            $attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Return the attendance data
            return json_encode(['success' => true, 'data' => $attendanceData]);
        } catch (Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // public static function getStudentsAttendanceBySection($db, $sectionId)
    // {
    //     try {
    //         $stmt = $db->prepare("SELECT s.student_id, s.name, a.absence_status, a.timestamp
    //                           FROM attendance a
    //                           JOIN student s ON a.student_id = s.student_id
    //                           WHERE a.section_id = :section_id");
    //         $stmt->bindParam(':section_id', $sectionId, PDO::PARAM_INT);
    //         $stmt->execute();

    //         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     } catch (Exception $e) {
    //         throw new Exception("Error fetching attendance data: " . $e->getMessage());
    //     }
    // }
}
