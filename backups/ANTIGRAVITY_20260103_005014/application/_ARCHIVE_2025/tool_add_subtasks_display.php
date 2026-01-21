<?php
// Utility script to add subtasks display to tasks.php
$file = 'tasks.php';
$content = file_get_contents($file);

// Find the task display section and add subtasks display
// We need to add subtasks display after the description column

$search = <<<'SEARCH'
                                        <!-- Description -->
                                        <div class="flex-grow-1 text-secondary" style="font-size: 0.85rem;">
                                            <?= htmlspecialchars($t['description'] ?? '') ?>
                                        </div>
SEARCH;

$replace = <<<'REPLACE'
                                        <!-- Description -->
                                        <div class="flex-shrink-0 text-secondary" style="width: 200px; font-size: 0.85rem;">
                                            <?= htmlspecialchars($t['description'] ?? '') ?>
                                        </div>
                                        
                                        <!-- Subtasks -->
                                        <div class="flex-shrink-0" style="width: 250px;">
                                            <?php if (!empty($t['subtasks'])): ?>
                                                <ul class="list-unstyled mb-0" style="font-size: 0.8rem;">
                                                    <?php foreach ($t['subtasks'] as $item): ?>
                                                        <li class="text-secondary">
                                                            <i class="far fa-<?= $item['is_completed'] ? 'check-square text-success' : 'square' ?> me-1"></i>
                                                            <?= htmlspecialchars($item['content']) ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
REPLACE;

$new_content = str_replace($search, $replace, $content);

if ($new_content === $content) {
    die("ERROR: Could not find target content to replace\n");
}

file_put_contents($file, $new_content);
echo "Subtasks display added successfully!\n";
?>
