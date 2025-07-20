<?php
// index.php - Main application file with authentication
require_once 'auth.php';
require_once 'GradeCalculator.php';

$auth = new Auth();

// Require login
$auth->requireLogin();

// Get current user
$currentUser = $auth->getCurrentUser();
$calculator = new GradeCalculator($currentUser['id']);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_semester']) && !empty($_POST['semester_name'])) {
        $calculator->addSemester($_POST['semester_name']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['add_subject'])) {
        $semesterId = $_POST['semester_id'];
        $subjectName = $_POST['subject_name'];
        $grade = $_POST['grade'];
        $units = $_POST['units'];
        
        if (!empty($subjectName) && !empty($grade) && !empty($units) && 
            $grade >= 1.0 && $grade <= 5.0 && $units > 0) {
            $calculator->addSubject($semesterId, $subjectName, $grade, $units);
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['update_subject'])) {
        $subjectId = $_POST['subject_id'];
        $subjectName = $_POST['subject_name'];
        $grade = $_POST['grade'];
        $units = $_POST['units'];
        
        if (!empty($subjectName) && !empty($grade) && !empty($units) && 
            $grade >= 1.0 && $grade <= 5.0 && $units > 0) {
            $calculator->updateSubject($subjectId, $subjectName, $grade, $units);
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['delete_semester'])) {
        $calculator->deleteSemester($_POST['semester_id']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['delete_subject'])) {
        $calculator->deleteSubject($_POST['subject_id']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle edit mode
$editingSubject = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editingSubject = $calculator->getSubject($_GET['edit']);
}

// Get data
$semesters = $calculator->getAllSemesters();
$cumulativeGWA = $calculator->calculateCumulativeGWA();
$totalUnits = $calculator->getTotalUnits();
$latinHonorStatus = $calculator->getLatinHonorStatus($cumulativeGWA);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Grade Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-6">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header with User Info -->
            <div class="flex justify-between items-center mb-8">
                <div class="text-center flex-1">
                    <div class="flex items-center justify-center mb-4">
                        <i class="fas fa-book text-blue-600 text-3xl mr-3"></i>
                        <h1 class="text-3xl font-bold text-gray-800">Academic Grade Calculator</h1>
                    </div>
                    <p class="text-gray-600">Track your grades, monitor GWA, and Latin Honors eligibility</p>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Welcome back,</p>
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                    </div>
                    <div class="relative">
                        <button id="userMenuBtn" class="flex items-center space-x-2 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-user-circle text-gray-600"></i>
                            <i id="dropdownArrow" class="fas fa-chevron-down text-gray-400 transition-transform duration-200"></i>
                        </button>
                        <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-10">
                            <div class="py-2">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm text-gray-600">Signed in as</p>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                                </div>
                                <a href="logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-sign-out-alt mr-2"></i>
                                    Sign Out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100">Cumulative GWA</p>
                            <p class="text-3xl font-bold"><?php echo number_format($cumulativeGWA, 3); ?></p>
                        </div>
                        <i class="fas fa-calculator text-3xl text-blue-200"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100">Total Units</p>
                            <p class="text-3xl font-bold"><?php echo $totalUnits; ?></p>
                        </div>
                        <i class="fas fa-chart-line text-3xl text-green-200"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100">Latin Honors</p>
                            <p class="text-lg font-bold"><?php echo $latinHonorStatus; ?></p>
                        </div>
                        <i class="fas fa-award text-3xl text-purple-200"></i>
                    </div>
                </div>
            </div>

            <!-- Latin Honors Reference -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Latin Honors Requirements</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">≤ 1.20</div>
                        <div class="text-sm text-gray-600">Summa Cum Laude</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">≤ 1.45</div>
                        <div class="text-sm text-gray-600">Magna Cum Laude</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">≤ 1.75</div>
                        <div class="text-sm text-gray-600">Cum Laude</div>
                    </div>
                </div>
            </div>

            <!-- Add New Semester -->
            <div class="bg-blue-50 rounded-xl p-6 mb-8">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Add New Semester</h3>
                <form method="POST" class="flex gap-4">
                    <input type="text" name="semester_name" placeholder="Semester name (e.g., 1st Semester 2023-2024)" 
                           required class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="submit" name="add_semester" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Semester
                    </button>
                </form>
            </div>

            <!-- Semesters -->
            <div class="space-y-6">
                <?php foreach ($semesters as $semester): ?>
                    <?php 
                        $semGPA = $calculator->calculateSemesterGPA($semester['subjects']);
                        $semUnits = array_sum(array_column($semester['subjects'], 'units'));
                    ?>
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($semester['semester_name']); ?></h3>
                                <div class="flex gap-6 mt-1">
                                    <span class="text-sm text-gray-600">
                                        GPA: <span class="font-semibold text-blue-600"><?php echo number_format($semGPA, 3); ?></span>
                                    </span>
                                    <span class="text-sm text-gray-600">
                                        Units: <span class="font-semibold text-green-600"><?php echo $semUnits; ?></span>
                                    </span>
                                </div>
                            </div>
                            <form method="POST" class="inline">
                                <input type="hidden" name="semester_id" value="<?php echo $semester['id']; ?>">
                                <button type="submit" name="delete_semester" 
                                        onclick="return confirm('Are you sure you want to delete this semester?')"
                                        class="text-red-500 hover:bg-red-100 p-2 rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        
                        <div class="p-6">
                            <!-- Add/Edit Grade Form -->
                            <?php if ($editingSubject && $editingSubject['semester_id'] == $semester['id']): ?>
                                <!-- Edit Form -->
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                    <h4 class="text-lg font-semibold text-yellow-800 mb-3">
                                        <i class="fas fa-edit mr-2"></i>Edit Subject
                                    </h4>
                                    <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                        <input type="hidden" name="subject_id" value="<?php echo $editingSubject['id']; ?>">
                                        <input type="text" name="subject_name" placeholder="Subject name" 
                                               value="<?php echo htmlspecialchars($editingSubject['subject_name']); ?>" required
                                               class="px-3 py-2 border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
                                        <input type="number" name="grade" placeholder="Grade (1.0-5.0)" 
                                               min="1.0" max="5.0" step="0.01" value="<?php echo $editingSubject['grade']; ?>" required
                                               class="px-3 py-2 border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
                                        <input type="number" name="units" placeholder="Units" 
                                               min="0.5" step="0.5" value="<?php echo $editingSubject['units']; ?>" required
                                               class="px-3 py-2 border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm">
                                        <div class="flex gap-2">
                                            <button type="submit" name="update_subject"
                                                    class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors text-sm flex-1">
                                                <i class="fas fa-save mr-2"></i>Update
                                            </button>
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>"
                                               class="bg-gray-500 text-white px-3 py-2 rounded-lg hover:bg-gray-600 transition-colors text-sm text-center">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <!-- Add Form -->
                                <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                                    <input type="hidden" name="semester_id" value="<?php echo $semester['id']; ?>">
                                    <input type="text" name="subject_name" placeholder="Subject name" required
                                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <input type="number" name="grade" placeholder="Grade (1.0-5.0)" 
                                           min="1.0" max="5.0" step="0.01" required
                                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <input type="number" name="units" placeholder="Units" 
                                           min="0.5" step="0.5" required
                                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <button type="submit" name="add_subject"
                                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                                        <i class="fas fa-plus mr-2"></i>Add Grade
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <!-- Grades Table -->
                            <?php if (!empty($semester['subjects'])): ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="border-b border-gray-200">
                                                <th class="text-left py-2 px-3 text-sm font-semibold text-gray-700">Subject</th>
                                                <th class="text-center py-2 px-3 text-sm font-semibold text-gray-700">Grade</th>
                                                <th class="text-center py-2 px-3 text-sm font-semibold text-gray-700">Units</th>
                                                <th class="text-center py-2 px-3 text-sm font-semibold text-gray-700">Weighted</th>
                                                <th class="text-center py-2 px-3 text-sm font-semibold text-gray-700">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($semester['subjects'] as $subject): ?>
                                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                                    <td class="py-3 px-3 text-sm"><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                    <td class="py-3 px-3 text-center text-sm font-medium"><?php echo number_format($subject['grade'], 2); ?></td>
                                                    <td class="py-3 px-3 text-center text-sm"><?php echo $subject['units']; ?></td>
                                                    <td class="py-3 px-3 text-center text-sm font-medium text-blue-600">
                                                        <?php echo number_format($subject['grade'] * $subject['units'], 2); ?>
                                                    </td>
                                                    <td class="py-3 px-3 text-center">
                                                        <div class="flex justify-center gap-2">
                                                            <a href="?edit=<?php echo $subject['id']; ?>"
                                                               class="text-blue-500 hover:bg-blue-100 p-1 rounded transition-colors"
                                                               title="Edit Subject">
                                                                <i class="fas fa-edit text-sm"></i>
                                                            </a>
                                                            <form method="POST" class="inline">
                                                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                                <button type="submit" name="delete_subject"
                                                                        onclick="return confirm('Are you sure you want to delete this subject?')"
                                                                        class="text-red-500 hover:bg-red-100 p-1 rounded transition-colors"
                                                                        title="Delete Subject">
                                                                    <i class="fas fa-trash text-sm"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-8">No grades added yet for this semester.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($semesters)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-book text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">Start by adding your first semester above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // User dropdown menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userDropdown = document.getElementById('userDropdown');
            const dropdownArrow = document.getElementById('dropdownArrow');

            userMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                
                if (userDropdown.classList.contains('hidden')) {
                    userDropdown.classList.remove('hidden');
                    dropdownArrow.style.transform = 'rotate(180deg)';
                } else {
                    userDropdown.classList.add('hidden');
                    dropdownArrow.style.transform = 'rotate(0deg)';
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                    dropdownArrow.style.transform = 'rotate(0deg)';
                }
            });

            // Prevent dropdown from closing when clicking inside it
            userDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>