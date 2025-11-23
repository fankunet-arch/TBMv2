<h2>规则指派控制台</h2>

<div style="display: flex; gap: 20px;">
    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd;">
        <h3>常规周循环 (Level 3)</h3>
        <form action="/smsys/assignments/save_routine" method="post">
            <table>
                <?php 
                $days = [1=>'周一', 2=>'周二', 3=>'周三', 4=>'周四', 5=>'周五', 6=>'周六', 7=>'周日'];
                foreach($days as $num => $name): 
                ?>
                <tr>
                    <td><?= $name ?></td>
                    <td>
                        <select name="weekly_<?= $num ?>" style="width:100%">
                            <option value="">-- 默认/不播放 --</option>
                            <?php foreach($strategies as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($map["WEEKLY_$num"]??0) == $s['id'] ? 'selected' : '' ?>>
                                    <?= $s['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr><td colspan="2"><hr></td></tr>
                <tr>
                    <td><strong>法定节假日 (Level 2)</strong></td>
                    <td>
                        <select name="holiday_strategy" style="width:100%">
                            <option value="">-- 保持周循环 --</option>
                            <?php foreach($strategies as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($map["HOLIDAY"]??0) == $s['id'] ? 'selected' : '' ?>>
                                    <?= $s['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:red">* 仅当日期被标记为“节假日”时生效</small>
                    </td>
                </tr>
            </table>
            <button type="submit" class="btn" style="margin-top:15px;">保存常规设置</button>
        </form>
    </div>

    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd;">
        <h3>特殊日历管理 (Level 1 & 2)</h3>
        <form action="/smsys/assignments/add_calendar" method="post" style="background:#f9f9f9; padding:10px; margin-bottom:10px;">
            日期: <input type="date" name="date" required>
            <br><br>
            类型: 
            <select name="type" id="cal_type" onchange="toggleSpec()">
                <option value="1">法定节假日 (Holiday)</option>
                <option value="2">特例活动 (Special)</option>
            </select>
            <br><br>
            <div id="spec_strat" style="display:none;">
                强制策略: 
                <select name="strategy_id">
                    <option value="">-- 选择策略 --</option>
                    <?php foreach($strategies as $s): echo "<option value='{$s['id']}'>{$s['name']}</option>"; endforeach; ?>
                </select>
            </div>
            <br>
            备注: <input type="text" name="desc">
            <button type="submit" class="btn">添加/更新</button>
        </form>

        <table style="font-size:12px;">
            <thead><tr><th>日期</th><th>类型</th><th>策略</th><th>操作</th></tr></thead>
            <tbody>
                <?php foreach($calendar as $c): ?>
                <tr>
                    <td><?= $c['calendar_date'] ?></td>
                    <td><?= $c['day_type'] == 1 ? '<span style="color:red">节假日</span>' : '<span style="color:blue;font-weight:bold">特例</span>' ?></td>
                    <td>
                        <?php 
                        if($c['day_type']==2) {
                            $sid = $map['SPECIAL_'.$c['calendar_date']] ?? 0;
                            echo $sid ? "ID:$sid" : '未指定';
                        } else {
                            echo "使用节假日通用";
                        }
                        ?>
                    </td>
                    <td><a href="/smsys/assignments/delete_calendar?date=<?= $c['calendar_date'] ?>" style="color:red">删</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleSpec() {
    var val = document.getElementById('cal_type').value;
    document.getElementById('spec_strat').style.display = (val == '2') ? 'block' : 'none';
}
</script>