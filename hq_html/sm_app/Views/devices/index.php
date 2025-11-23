<h2>设备监控与审批</h2>

<div class="tip" style="background:#eef; padding:10px; margin-bottom:20px; font-size:12px;">
    说明：新设备连接后默认为“待激活”状态，不会下载任何音乐。请在确认设备归属后点击“激活”。
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>MAC 地址</th>
            <th>状态 (Status)</th>
            <th>绑定门店 ID</th>
            <th>设备别名</th>
            <th>最后在线</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($devices as $d): 
            $offline = (time() - strtotime($d['last_heartbeat'])) > 600;
            $statusLabel = '';
            $rowClass = '';
            
            if ($d['status'] == 0) {
                $statusLabel = '<span style="color:red; font-weight:bold;">⏳ 待激活 (Pending)</span>';
                $rowClass = 'background: #fff0f0;';
            } elseif ($d['status'] == 2) {
                $statusLabel = '<span style="color:gray;">🚫 已禁用</span>';
            } else {
                $statusLabel = '<span style="color:green;">✅ 正常</span>';
            }
        ?>
        <tr style="<?= $rowClass ?>">
            <td><?= $d['id'] ?></td>
            <td style="font-family:monospace"><?= $d['mac_address'] ?></td>
            <td><?= $statusLabel ?></td>
            <td><?= $d['shop_id'] ?: '-' ?></td>
            <td><?= htmlspecialchars($d['device_name'] ?? '-') ?></td>
            <td>
                <?= $d['last_heartbeat'] ?>
                <?= $offline ? '<span style="color:red;font-size:10px">(离线)</span>' : '<span style="color:green;font-size:10px">(在线)</span>' ?>
            </td>
            <td>
                <?php if ($d['status'] == 0): ?>
                    <!-- 激活表单 -->
                    <form action="/smsys/devices/activate" method="post" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <input type="text" name="shop_id" placeholder="门店ID" required style="width:60px; padding:2px;">
                        <input type="text" name="device_name" placeholder="别名(如:大堂)" style="width:80px; padding:2px;">
                        <button type="submit" class="btn" style="background:#28a745; padding:2px 8px; font-size:12px;">激活</button>
                    </form>
                <?php elseif ($d['status'] == 1): ?>
                    <a href="/smsys/devices/block?id=<?= $d['id'] ?>" onclick="return confirm('确定禁用该设备？')" style="color:red; font-size:12px;">禁用</a>
                <?php else: ?>
                    <span style="color:#ccc;">-</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>