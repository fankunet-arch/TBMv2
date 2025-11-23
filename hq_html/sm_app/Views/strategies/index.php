<h2>策略排期管理</h2>
<div style="margin-bottom: 20px;">
    <a href="/smsys/strategies/edit" class="btn" style="background: #28a745;">+ 新建时间轴策略</a>
</div>
<table>
    <thead>
        <tr><th>ID</th><th>策略名称</th><th>包含时段数</th><th>操作</th></tr>
    </thead>
    <tbody>
        <?php foreach ($strategies as $s): 
            $tl = json_decode($s['timeline_json'], true);
            $count = is_array($tl) ? count($tl) : 0;
        ?>
        <tr>
            <td><?= $s['id'] ?></td>
            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
            <td><?= $count ?> 个时段</td>
            <td>
                <a href="/smsys/strategies/edit?id=<?= $s['id'] ?>" class="btn">编辑</a>
                <a href="/smsys/strategies/delete?id=<?= $s['id'] ?>" class="btn btn-danger" onclick="return confirm('删除将影响已应用此策略的门店，确定？');">删除</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>