<h2><?= $strategy ? '编辑策略' : '新建策略' ?></h2>

<form action="/smsys/strategies/save" method="post">
    <input type="hidden" name="id" value="<?= $strategy['id'] ?? 0 ?>">
    
    <div style="margin-bottom: 20px;">
        <label>策略名称:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($strategy['name'] ?? '') ?>" required style="padding:5px; width:300px;">
    </div>

    <h3>时间轴配置</h3>
    <p style="color:#666; font-size:12px;">请按时间顺序添加。确保时间段不重叠（例如 08:00-12:00, 12:00-18:00）。</p>
    
    <table id="timeline-table">
        <thead><tr><th>开始时间</th><th>结束时间</th><th>播放歌单</th><th>操作</th></tr></thead>
        <tbody id="timeline-body">
            </tbody>
    </table>
    
    <button type="button" class="btn" onclick="addRow()" style="margin-top:10px; background:#17a2b8;">+ 添加时段</button>
    
    <div style="margin-top: 30px;">
        <button type="submit" class="btn" style="padding: 10px 30px;">保存策略</button>
    </div>
</form>

<script>
// 预加载数据
const savedData = <?= $strategy['timeline_json'] ?? '[]' ?>;
const allPlaylists = <?= json_encode($playlists) ?>;

function addRow(data = null) {
    const tbody = document.getElementById('timeline-body');
    const tr = document.createElement('tr');
    
    let options = '<option value="">-- 选择歌单 --</option>';
    allPlaylists.forEach(p => {
        const selected = (data && data.playlist_id == p.id) ? 'selected' : '';
        options += `<option value="${p.id}" ${selected}>${p.name}</option>`;
    });

    tr.innerHTML = `
        <td><input type="time" name="start_time[]" value="${data ? data.start : '09:00'}" required></td>
        <td><input type="time" name="end_time[]" value="${data ? data.end : '12:00'}" required></td>
        <td><select name="playlist_id[]" required>${options}</select></td>
        <td><button type="button" class="btn btn-danger" onclick="this.closest('tr').remove()">移除</button></td>
    `;
    tbody.appendChild(tr);
}

// 初始化
if (savedData.length > 0) {
    savedData.forEach(item => addRow(item));
} else {
    addRow(); // 默认一行
}
</script>