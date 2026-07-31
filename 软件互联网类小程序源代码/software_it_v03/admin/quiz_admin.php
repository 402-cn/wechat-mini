<?php
/**
 * 筑码引擎 www.402.cn
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/layout.php';
require_once dirname(__DIR__) . '/api/core/bootstrap.php';
$id = trim($_GET['id'] ?? '');
if ($id === '') { header('Location: dashboard.php'); exit; }
$pdo = db();
$msg = '';
if (isset($_GET['del'])) {
    $pdo->prepare('DELETE FROM widget_items WHERE id = ? AND instance_id = ?')->execute([(int)$_GET['del'], $id]);
    $msg = '题目已删除';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_props'])) {
        $props = json_decode($pdo->query("SELECT props_json FROM widget_instances WHERE instance_id=" . $pdo->quote($id))->fetchColumn() ?: '{}', true) ?: [];
        $props['title'] = trim($_POST['title'] ?? '');
        $props['requireLogin'] = !empty($_POST['requireLogin']);
        $props['maxAttempts'] = (int)($_POST['maxAttempts'] ?? 0);
        $props['passScore'] = (int)($_POST['passScore'] ?? 60);
        $pdo->prepare('UPDATE widget_instances SET props_json=? WHERE instance_id=?')->execute([
            json_encode($props, JSON_UNESCAPED_UNICODE), $id,
        ]);
        $msg = '配置已保存';
    }
    if (isset($_POST['save_question'])) {
        $qid = (int)($_POST['question_id'] ?? 0);
        if ($qid > 0) {
            $opts = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['options'] ?? '')))));
            $q = [
                'question' => trim($_POST['question'] ?? ''),
                'options' => $opts,
                'answer' => trim($_POST['answer'] ?? ''),
                'type' => trim($_POST['qtype'] ?? 'single'),
            ];
            $pdo->prepare('UPDATE widget_items SET item_json=? WHERE id=? AND instance_id=?')->execute([
                json_encode($q, JSON_UNESCAPED_UNICODE), $qid, $id,
            ]);
            $msg = '题目已更新';
        }
    }
    if (isset($_POST['add_question'])) {
        $opts = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['options'] ?? '')))));
        $optArr = [];
        foreach ($opts as $o) { $optArr[] = $o; }
        $q = [
            'question' => trim($_POST['question'] ?? ''),
            'options' => $optArr,
            'answer' => trim($_POST['answer'] ?? ''),
            'type' => trim($_POST['qtype'] ?? 'single'),
        ];
        $maxSort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM widget_items WHERE instance_id=" . $pdo->quote($id))->fetchColumn();
        $pdo->prepare('INSERT INTO widget_items (instance_id,item_key,item_json,sort_order) VALUES (?,?,?,?)')->execute([
            $id, 'item', json_encode($q, JSON_UNESCAPED_UNICODE), $maxSort + 1,
        ]);
        $msg = '题目已添加';
    }
    if (isset($_POST['save_order'])) {
        $ids = json_decode((string)($_POST['question_order'] ?? '[]'), true);
        if (is_array($ids)) {
            $upd = $pdo->prepare('UPDATE widget_items SET sort_order=? WHERE id=? AND instance_id=?');
            foreach ($ids as $i => $qid) {
                $upd->execute([(int)$i, (int)$qid, $id]);
            }
            $msg = '题目排序已保存';
        }
    }
}
$stmt = $pdo->prepare('SELECT * FROM widget_instances WHERE instance_id = ? AND component_type = ? LIMIT 1');
$stmt->execute([$id, 'quiz']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '答题组件不存在'; exit; }
$props = json_decode($row['props_json'] ?? '{}', true) ?: [];
$items = $pdo->prepare('SELECT * FROM widget_items WHERE instance_id = ? AND status = 1 ORDER BY sort_order ASC, id ASC');
$items->execute([$id]);
$questions = $items->fetchAll(PDO::FETCH_ASSOC);
admin_layout_start($row['label'] . ' · 题库管理', 'quiz_admin.php?id=' . $id, $id, '悬停「?」查看前台答题组件位置。请用下方表单维护题目，无需 JSON。');
if ($msg) echo '<div class="msg msg-ok">' . htmlspecialchars($msg) . '</div>';
echo '<div class="card"><form method="post" class="form-grid"><input type="hidden" name="save_props" value="1">';
admin_field_text('标题', 'title', (string)($props['title'] ?? '在线答题'));
admin_field_text('及格分(百分制)', 'passScore', (string)($props['passScore'] ?? 60), 'number');
admin_field_text('最多答题次数(0=不限)', 'maxAttempts', (string)($props['maxAttempts'] ?? 0), 'number');
echo '<label><input type="checkbox" name="requireLogin" value="1"' . (!empty($props['requireLogin']) ? ' checked' : '') . '> 需登录</label>';
echo '<p><button class="btn" type="submit">保存配置</button></p></form></div>';
echo '<div class="card"><h3>题目列表 <small style="font-weight:400;color:#999">拖拽左侧把手调整顺序</small></h3>';
if ($questions) {
    echo '<form method="post" id="quiz-order-form" style="margin-bottom:12px">';
    echo '<input type="hidden" name="save_order" value="1"><input type="hidden" name="question_order" id="question_order_json" value="">';
    echo '<div id="quiz-sortable">';
    foreach ($questions as $qi => $q) {
        $data = json_decode($q['item_json'] ?? '{}', true) ?: [];
        $qtext = (string)($data['question'] ?? '');
        echo '<div class="quiz-sort-item" data-id="' . (int)$q['id'] . '" style="border:1px solid #eee;border-radius:8px;padding:12px;margin-bottom:12px;background:#fafafa">';
        echo '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">';
        echo '<span class="drag-handle" title="拖拽排序" style="cursor:grab;color:#999;font-size:18px;line-height:1">☰</span>';
        echo '<strong style="flex:1">题目 #' . ($qi + 1) . '</strong>';
        echo '<a class="btn btn-sm btn-danger" href="?id=' . urlencode($id) . '&del=' . (int)$q['id'] . '" onclick="return confirm(\'确认删除?\')">删除</a>';
        echo '</div>';
        echo '<form method="post" class="form-grid">';
        echo '<input type="hidden" name="save_question" value="1"><input type="hidden" name="question_id" value="' . (int)$q['id'] . '">';
        admin_field_text('题目', 'question', $qtext);
        echo '<label>选项(每行一个)</label><textarea name="options" rows="4" style="width:100%">' . htmlspecialchars(implode("\n", (array)($data['options'] ?? []))) . '</textarea>';
        admin_field_text('正确答案', 'answer', (string)($data['answer'] ?? ''), 'text');
        admin_field_select('题型', 'qtype', ['single' => '单选', 'multi' => '多选(逗号分隔答案)'], (string)($data['type'] ?? 'single'));
        echo '<p><button class="btn btn-sm" type="submit">保存本题</button></p></form></div>';
    }
    echo '</div><p><button class="btn btn-sm" type="submit">保存排序</button></p></form>';
} else {
    echo '<p style="color:#999">暂无题目，请添加。</p>';
}
echo '<form method="post" class="form-grid" style="margin-top:16px"><input type="hidden" name="add_question" value="1">';
admin_field_text('题目', 'question');
echo '<label>选项(每行一个)</label><textarea name="options" rows="4" style="width:100%"></textarea>';
admin_field_text('正确答案', 'answer', '', 'text');
admin_field_select('题型', 'qtype', ['single' => '单选', 'multi' => '多选(逗号分隔答案)'], 'single');
echo '<p><button class="btn" type="submit">添加题目</button></p></form></div>';
echo '<script src="' . htmlspecialchars(asset_url('../assets/vendor/sortablejs/Sortable.min.js')) . '"></script>';
echo '<script>
function syncQuizOrder(){
  var box=document.getElementById("quiz-sortable"); if(!box) return true;
  var ids=[]; box.querySelectorAll(".quiz-sort-item[data-id]").forEach(function(el){ ids.push(parseInt(el.getAttribute("data-id"),10)); });
  var inp=document.getElementById("question_order_json"); if(inp) inp.value=JSON.stringify(ids);
  return true;
}
var quizSortEl=document.getElementById("quiz-sortable");
if(quizSortEl&&quizSortEl.querySelector(".quiz-sort-item")){
  Sortable.create(quizSortEl,{handle:".drag-handle",animation:150,ghostClass:"sortable-ghost",onEnd:syncQuizOrder});
}
var orderForm=document.getElementById("quiz-order-form");
if(orderForm){ orderForm.addEventListener("submit", syncQuizOrder); syncQuizOrder(); }
</script>';
admin_layout_end();
