<?php
// Make page fixed height with scrollable task list
$file = 'tasks.php';
$content = file_get_contents($file);

// Add CSS to make the page fixed and task list scrollable
$search_style = '</style>';

$add_style = <<<'STYLE'
/* Fixed page layout - no page scroll, only task list scroll */
body {
    overflow: hidden;
    height: 100vh;
}

.ag-main-content {
    height: calc(100vh - 60px); /* Adjust based on header height */
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.card {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.tab-content {
    flex: 1;
    overflow: hidden;
}

.tab-pane {
    height: 100%;
    overflow: hidden;
}

.tab-pane .row {
    height: 100%;
}

.tab-pane .col-md-7 {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.tab-pane .col-md-5 {
    height: 100%;
    overflow-y: auto;
}

/* Scrollable task list */
.tab-pane .list-group {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
}

/* Sticky header stays at top of scrollable area */
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

</style>
STYLE;

$content = str_replace($search_style, $add_style, $content);

file_put_contents($file, $content);
echo "Fixed page layout with scrollable task list applied!\n";
?>
