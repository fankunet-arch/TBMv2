<h2>曲库管理</h2>

<div style="background: #f9f9f9; padding: 15px; border: 1px dashed #ccc; margin-bottom: 20px;">
    <form action="/smsys/songs/upload" method="post" enctype="multipart/form-data">
        <strong>上传新歌：</strong>
        <input type="file" name="mp3_file" accept=".mp3,.aac" required>
        <button type="submit" class="btn">开始上传</button>
        <p style="font-size: 12px; color: #666; margin-top: 5px;">
            * 支持 MP3/AAC 格式。系统将自动计算 MD5，重复文件不会被重复录入。
        </p>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>文件名/标题</th>
            <th>MD5 (指纹)</th>
            <th>大小</th>
            <th>试听</th>
            <th>上传时间</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($songs as $song): ?>
        <tr>
            <td><?= $song['id'] ?></td>
            <td><?= htmlspecialchars($song['title']) ?></td>
            <td style="font-family: monospace; font-size: 12px;"><?= $song['file_md5'] ?></td>
            <td><?= round($song['file_size'] / 1024 / 1024, 2) ?> MB</td>
            <td>
                <audio controls style="height: 30px;">
                    <source src="<?= $song['file_url'] ?>" type="audio/mpeg">
                </audio>
            </td>
            <td><?= $song['created_at'] ?></td>
            <td>
                <a href="/smsys/songs/delete?id=<?= $song['id'] ?>" class="btn btn-danger" onclick="return confirm('确定删除？');">删除</a>
            </td>
        </tr>
        <?php endforeach; ?>
        
        <?php if (empty($songs)): ?>
        <tr>
            <td colspan="7" style="text-align: center; padding: 20px;">暂无歌曲，请上传。</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>