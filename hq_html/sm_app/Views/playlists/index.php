<h2>歌单管理</h2>

<div style="margin-bottom: 20px;">
    <a href="/smsys/playlists/edit" class="btn" style="background: #28a745;">+ 新建歌单</a>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>歌单名称</th>
            <th>播放模式</th>
            <th>歌曲数量</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($playlists as $list): ?>
        <tr>
            <td><?= $list['id'] ?></td>
            <td>
                <strong><?= htmlspecialchars($list['name']) ?></strong>
            </td>
            <td>
                <?php if($list['play_mode'] == 'random'): ?>
                    <span style="color: orange;">随机播放</span>
                <?php else: ?>
                    <span style="color: blue;">顺序播放</span>
                <?php endif; ?>
            </td>
            <td><?= $list['count'] ?> 首</td>
            <td><?= $list['created_at'] ?></td>
            <td>
                <a href="/smsys/playlists/edit?id=<?= $list['id'] ?>" class="btn">编辑/选歌</a>
                <a href="/smsys/playlists/delete?id=<?= $list['id'] ?>" class="btn btn-danger" onclick="return confirm('确定删除？');" style="margin-left: 5px;">删除</a>
            </td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($playlists)): ?>
        <tr><td colspan="6" style="text-align: center; padding: 20px;">暂无歌单，请新建。</td></tr>
        <?php endif; ?>
    </tbody>
</table>