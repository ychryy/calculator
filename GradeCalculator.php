<?php
// GradeCalculator.php - Updated Main calculator class with user support
require_once 'database.php';

class GradeCalculator {
    private $db;
    private $userId;

    public function __construct($userId = null) {
        $this->db = new Database();
        $this->userId = $userId;
    }

    // Set user ID
    public function setUserId($userId) {
        $this->userId = $userId;
    }

    // Add new semester
    public function addSemester($semesterName) {
        if (!$this->userId) return false;
        
        $stmt = $this->db->getConnection()->prepare("INSERT INTO semesters (user_id, semester_name) VALUES (?, ?)");
        return $stmt->execute([$this->userId, $semesterName]);
    }

    // Add subject to semester
    public function addSubject($semesterId, $subjectName, $grade, $units) {
        // Verify semester belongs to current user
        if (!$this->verifySemesterOwnership($semesterId)) return false;
        
        $stmt = $this->db->getConnection()->prepare(
            "INSERT INTO subjects (semester_id, subject_name, grade, units) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$semesterId, $subjectName, $grade, $units]);
    }

    // Get all semesters with subjects for current user
    public function getAllSemesters() {
        if (!$this->userId) return [];
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT s.id, s.semester_name, s.created_at,
                   sub.id as subject_id, sub.subject_name, sub.grade, sub.units
            FROM semesters s
            LEFT JOIN subjects sub ON s.id = sub.semester_id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC, sub.subject_name
        ");
        
        $stmt->execute([$this->userId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->groupSemesterData($result);
    }

    // Group semester data
    private function groupSemesterData($data) {
        $semesters = [];
        foreach ($data as $row) {
            $semId = $row['id'];
            if (!isset($semesters[$semId])) {
                $semesters[$semId] = [
                    'id' => $row['id'],
                    'semester_name' => $row['semester_name'],
                    'created_at' => $row['created_at'],
                    'subjects' => []
                ];
            }
            
            if ($row['subject_id']) {
                $semesters[$semId]['subjects'][] = [
                    'id' => $row['subject_id'],
                    'subject_name' => $row['subject_name'],
                    'grade' => $row['grade'],
                    'units' => $row['units']
                ];
            }
        }
        return array_values($semesters);
    }

    // Verify semester belongs to current user
    private function verifySemesterOwnership($semesterId) {
        if (!$this->userId) return false;
        
        $stmt = $this->db->getConnection()->prepare("SELECT id FROM semesters WHERE id = ? AND user_id = ?");
        $stmt->execute([$semesterId, $this->userId]);
        return $stmt->fetch() !== false;
    }

    // Verify subject belongs to current user (through semester)
    private function verifySubjectOwnership($subjectId) {
        if (!$this->userId) return false;
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT sub.id FROM subjects sub
            INNER JOIN semesters s ON sub.semester_id = s.id
            WHERE sub.id = ? AND s.user_id = ?
        ");
        $stmt->execute([$subjectId, $this->userId]);
        return $stmt->fetch() !== false;
    }

    // Delete semester
    public function deleteSemester($semesterId) {
        if (!$this->verifySemesterOwnership($semesterId)) return false;
        
        $stmt = $this->db->getConnection()->prepare("DELETE FROM semesters WHERE id = ? AND user_id = ?");
        return $stmt->execute([$semesterId, $this->userId]);
    }

    // Delete subject
    public function deleteSubject($subjectId) {
        if (!$this->verifySubjectOwnership($subjectId)) return false;
        
        $stmt = $this->db->getConnection()->prepare("
            DELETE sub FROM subjects sub
            INNER JOIN semesters s ON sub.semester_id = s.id
            WHERE sub.id = ? AND s.user_id = ?
        ");
        return $stmt->execute([$subjectId, $this->userId]);
    }

    // Update subject
    public function updateSubject($subjectId, $subjectName, $grade, $units) {
        if (!$this->verifySubjectOwnership($subjectId)) return false;
        
        $stmt = $this->db->getConnection()->prepare("
            UPDATE subjects sub
            INNER JOIN semesters s ON sub.semester_id = s.id
            SET sub.subject_name = ?, sub.grade = ?, sub.units = ?
            WHERE sub.id = ? AND s.user_id = ?
        ");
        return $stmt->execute([$subjectName, $grade, $units, $subjectId, $this->userId]);
    }

    // Get single subject
    public function getSubject($subjectId) {
        if (!$this->verifySubjectOwnership($subjectId)) return false;
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT sub.* FROM subjects sub
            INNER JOIN semesters s ON sub.semester_id = s.id
            WHERE sub.id = ? AND s.user_id = ?
        ");
        $stmt->execute([$subjectId, $this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Calculate semester GPA
    public function calculateSemesterGPA($subjects) {
        if (empty($subjects)) return 0;
        
        $totalWeightedGrades = 0;
        $totalUnits = 0;
        
        foreach ($subjects as $subject) {
            $totalWeightedGrades += $subject['grade'] * $subject['units'];
            $totalUnits += $subject['units'];
        }
        
        return $totalUnits > 0 ? $totalWeightedGrades / $totalUnits : 0;
    }

    // Calculate cumulative GWA for current user
    public function calculateCumulativeGWA() {
        if (!$this->userId) return 0;
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT sub.grade, sub.units FROM subjects sub
            INNER JOIN semesters s ON sub.semester_id = s.id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($subjects)) return 0;
        
        $totalWeightedGrades = 0;
        $totalUnits = 0;
        
        foreach ($subjects as $subject) {
            $totalWeightedGrades += $subject['grade'] * $subject['units'];
            $totalUnits += $subject['units'];
        }
        
        return $totalUnits > 0 ? $totalWeightedGrades / $totalUnits : 0;
    }

    // Get total units for current user
    public function getTotalUnits() {
        if (!$this->userId) return 0;
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT SUM(sub.units) as total FROM subjects sub
            INNER JOIN semesters s ON sub.semester_id = s.id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // Get Latin Honor status
    public function getLatinHonorStatus($gwa) {
        if ($gwa <= 1.20) return 'Summa Cum Laude';
        if ($gwa <= 1.45) return 'Magna Cum Laude';
        if ($gwa <= 1.75) return 'Cum Laude';
        return 'No Latin Honors';
    }
}