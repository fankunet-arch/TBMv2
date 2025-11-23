<h2><?= $playlist ? '编辑歌单' : '新建歌单' ?></h2>

<form action="/smsys/playlists/save" method="post">
    <input type="hidden" name="id" value="<?= $playlist['id'] ?? 0 ?>">

    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px;">
        <div style="margin-bottom: 15px;">
            <label style="display:block; font-weight:bold; margin-bottom:5px;">歌单名称:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($playlist['name'] ?? '') ?>" required style="width: 300px; padding: 8px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display:block; font-weight:bold; margin-bottom:5px;">播放模式:</label>
            <select name="play_mode" style="width: 300px; padding: 8px;">
                <option value="sequence" <?= ($playlist['play_mode']??'') == 'sequence' ? 'selected' : '' ?>>Sequence (顺序播放)</option>
                <option value="random" <?= ($playlist['play_mode']??'') == 'random' ? 'selected' : '' ?>>Random (随机打乱)</option>
            </select>
        </div>
    </div>

    <div style="background: #fff; padding: 20px; border: 1px solid #ddd;">
        <h3>选择歌曲</h3>
        <p style="font-size: 12px; color: #666;">请勾选要加入此歌单的音乐：</p>
        
        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px;">
            <?php if(empty($all_songs)): ?>
                <p>曲库为空，请先去 <a href="/smsys/songs/index">上传歌曲</a>。</p>
            <?php else: ?>
                <table style="border: none;">
                    <?php foreach ($all_songs as $song): 
                        $isChecked = in_array($song['id'], $selected_ids);
                    ?>
                    <tr style="background: <?= $isChecked ? '#e8f0fe' : 'transparent' ?>;">
                        <td style="width: 40px; text-align: center; border: none; border-bottom: 1px solid #eee;">
                            <input type="checkbox" name="song_ids[]" value="<?= $song['id'] ?>" id="s_<?= $song['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                        </td>
                        <td style="border: none; border-bottom: 1px solid #eee;">
                            <label for="s_<?= $song['id'] ?>" style="display: block; cursor: pointer; width: 100%;">
                                <strong><?= htmlspecialchars($song['title']) ?></strong> 
                                <span style="color: #999; font-size: 12px;">(<?= round($song['file_size']/1024/1024, 1) ?>MB)</span>
                            </label>
                        </td>
                        <td style="width: 150px; text-align: right; border: none; border-bottom: 1px solid #eee;">
                             <audio controls style="height: 20px; width: 100px;">
                                <source src="<?= $song['file_url'] ?>" type="audio/mpeg">
                            </audio>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" class="btn" style="padding: 10px 30px; font-size: 16px;">保存歌单</button>
        <a href="/smsys/playlists/index" style="margin-left: 20px; color: #666;">取消</a>
    </div>
</form>