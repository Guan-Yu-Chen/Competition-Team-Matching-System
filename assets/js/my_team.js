// Modal 開關
function showModal(html) {
    document.getElementById('modalContent').innerHTML = html;
    document.getElementById('mainModal').classList.add('active');
}

// 事件委派，確保所有 open-modal 按鈕都能正常觸發
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.open-modal');
    if (!btn) return;
    const type = btn.getAttribute('data-modal-type');
    if (type === 'profile') {
        fetch('my_team.php?ajax=profile&uid=' + encodeURIComponent(btn.getAttribute('data-uid')))
            .then(res => res.json())
            .then(data => {
                let html = `<h5>${data.name || ''}</h5>
                    <div><strong>技能：</strong>${(data.skills && data.skills.length) ? data.skills.join(', ') : '無'}</div>
                    <div><strong>作品集：</strong>${(data.portfolio && data.portfolio.length) ? data.portfolio.join(', ') : '無'}</div>
                    <div><strong>獎項：</strong>${(data.awards && data.awards.length) ? data.awards.join(', ') : '無'}</div>`;
                showModal(html);
            });
    } else if (type === 'ratings') {
        fetch('my_team.php?ajax=ratings&uid=' + encodeURIComponent(btn.getAttribute('data-uid')))
            .then(res => res.json())
            .then(data => {
                let html = `<h5>收到的評論</h5>`;
                if (data.length === 0) {
                    html += '<div class="text-muted">尚無評論</div>';
                } else {
                    html += `<table class="table table-bordered"><thead><tr><th>評論者</th><th>評分</th><th>評論</th></tr></thead><tbody>`;
                    data.forEach(r => {
                        html += `<tr><td>${r.ReviewerName || r.Reviewer}</td><td>${r.Rating}</td><td>${r.Comment}</td></tr>`;
                    });
                    html += '</tbody></table>';
                }
                showModal(html);
            });
    } else if (type === 'edit-rating') {
        const uid = btn.getAttribute('data-uid');
        fetch('my_team.php?ajax=edit-rating&uid=' + encodeURIComponent(uid))
            .then(res => res.json())
            .then(data => {
                let html = `<h5>新增/編輯評論</h5>
                    <form id="editRatingForm">
                        <div class="mb-2">
                            <label>評分：</label>
                            <input type="number" name="rating" min="1" max="5" class="form-control" value="${data.Rating || ''}">
                        </div>
                        <div class="mb-2">
                            <label>評論：</label>
                            <textarea name="comment" class="form-control">${data.Comment || ''}</textarea>
                        </div>
                        <input type="hidden" name="uid" value="${uid}">
                        <button type="submit" class="btn btn-primary" id="submitRatingBtn" disabled>提交</button>
                        <button type="button" class="btn btn-danger" id="deleteRatingBtn">刪除</button>
                        <button type="button" class="btn btn-secondary" id="cancelModalBtn">取消</button>
                    </form>`;
                showModal(html);

                // 新增輸入檢查，兩欄都要有資料才能啟用提交
                const ratingInput = document.querySelector('#editRatingForm input[name="rating"]');
                const commentInput = document.querySelector('#editRatingForm textarea[name="comment"]');
                const submitBtn = document.getElementById('submitRatingBtn');
                function checkInputs() {
                    if (ratingInput.value.trim() && commentInput.value.trim()) {
                        submitBtn.disabled = false;
                    } else {
                        submitBtn.disabled = true;
                    }
                }
                ratingInput.addEventListener('input', checkInputs);
                commentInput.addEventListener('input', checkInputs);
                checkInputs();

                document.getElementById('editRatingForm').onsubmit = function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('my_team.php?ajax=save-rating', {
                        method: 'POST',
                        body: formData
                    }).then(res => res.json()).then(r => {
                        if (r.success) {
                            document.getElementById('mainModal').classList.remove('active');
                            setTimeout(() => location.reload(), 300);
                        }
                    });
                };
                document.getElementById('deleteRatingBtn').onclick = function() {
                    fetch('my_team.php?ajax=delete-rating', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'uid=' + encodeURIComponent(uid)
                    }).then(res => res.json()).then(r => {
                        if (r.success) {
                            document.getElementById('mainModal').classList.remove('active');
                            setTimeout(() => location.reload(), 300);
                        }
                    });
                };
                document.getElementById('cancelModalBtn').onclick = function() {
                    document.getElementById('mainModal').classList.remove('active');
                };
            });
    } else if (type === 'edit-skill') {
        const tid = btn.getAttribute('data-team-id');
        const skills = btn.getAttribute('data-skills') || '';
        let html = `<h5>編輯隊伍技能需求</h5>
            <form id="editSkillForm">
                <div class="mb-2">
                    <label>技能（用逗號分隔）</label>
                    <input type="text" class="form-control" name="skills" value="${skills}">
                </div>
                <input type="hidden" name="tid" value="${tid}">
                <button type="submit" class="btn btn-primary">儲存</button>
                <button type="button" class="btn btn-secondary" id="cancelModalBtn">取消</button>
            </form>`;
        showModal(html);

        document.getElementById('editSkillForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('my_team.php?ajax=edit-skill', {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(r => {
                if (r.success) {
                    document.getElementById('mainModal').classList.remove('active');
                    setTimeout(() => location.reload(), 300);
                }
            });
        };
        document.getElementById('cancelModalBtn').onclick = function() {
            document.getElementById('mainModal').classList.remove('active');
        };
    } else if (type === 'competition') {
        fetch('my_team.php?ajax=competition&cid=' + encodeURIComponent(btn.getAttribute('data-cid')))
            .then(res => res.json())
            .then(data => {
                let html = `<h5>${data.Name || ''}</h5>
                    <div><strong>ID：</strong>${data.CID || ''}</div>
                    <div><strong>主辦單位：</strong>${data.Organizing_Units || ''}</div>
                    <div><strong>領域：</strong>${data.Field || ''}</div>
                    <div><strong>報名截止日：</strong>${data.Registration_Deadline || ''}</div>
                    <div><strong>獎金：</strong>${data.Prize_Money || ''}</div>
                    <div><strong>參賽資格：</strong>${data.Eligibility_Requirements || ''}</div>
                    <div><strong>每組人數：</strong>${data.Required_Number || ''}</div>`;
                showModal(html);
            });
    } else if (type === 'leave-competition') {
        let compName = btn.getAttribute('data-comp-name');
        let cid = btn.getAttribute('data-cid');
        let tid = btn.getAttribute('data-team-id');
        let html = `<div class="mb-2">確定要退出 ${compName} 競賽嗎？</div>
            <button type="button" class="btn btn-danger" id="confirmLeaveCompBtn">確認退出</button>
            <button type="button" class="btn btn-secondary" id="cancelModalBtn">取消</button>`;
        showModal(html);
        document.getElementById('confirmLeaveCompBtn').onclick = function() {
            fetch('my_team.php?ajax=leave-competition', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'cid=' + encodeURIComponent(cid) + '&tid=' + encodeURIComponent(tid)
            }).then(res => res.json()).then(r => {
                if (r.success) location.reload();
            });
        };
        document.getElementById('cancelModalBtn').onclick = function() {
            document.getElementById('mainModal').classList.remove('active');
        };
    } else if (type === 'leave-team') {
        let teamName = btn.getAttribute('data-team-name');
        let tid = btn.getAttribute('data-team-id');
        let html = `<div class="mb-2">確定要退出 ${teamName} 隊伍嗎？</div>
            <button type="button" class="btn btn-danger" id="confirmLeaveTeamBtn">確認退出</button>
            <button type="button" class="btn btn-secondary" id="cancelModalBtn">取消</button>`;
        showModal(html);
        document.getElementById('confirmLeaveTeamBtn').onclick = function() {
            fetch('my_team.php?ajax=leave-team', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'tid=' + encodeURIComponent(tid)
            }).then(res => res.json()).then(r => {
                if (r.success) location.href = 'my_team.php';
            });
        };
        document.getElementById('cancelModalBtn').onclick = function() {
            document.getElementById('mainModal').classList.remove('active');
        };
    } else if (type === 'invite') {
        const tid = btn.getAttribute('data-team-id');
        let html = `<h5>邀請學生加入隊伍</h5>
            <form id="inviteForm">
                <div class="mb-2">
                    <label>學生 SID</label>
                    <input type="text" class="form-control" name="sid" required>
                </div>
                <input type="hidden" name="tid" value="${tid}">
                <button type="submit" class="btn btn-primary">確認</button>
                <button type="button" class="btn btn-secondary" id="cancelModalBtn">取消</button>
            </form>`;
        showModal(html);

        document.getElementById('inviteForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('my_team.php?ajax=invite', {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(r => {
                let msg = '';
                if (r.success) {
                    msg = `<div class="text-success">邀請 ${r.invitee_name} 成功！</div>`;
                } else if (r.reason === 'already_in_team') {
                    msg = `<div class="text-danger">該學生已經在隊伍內，無法重複邀請。</div>`;
                } else if (r.reason === 'already_invited') {
                    msg = `<div class="text-warning">已經邀請過該學生，請勿重複邀請。</div>`;
                } else {
                    msg = `<div class="text-danger">邀請失敗，請確認 SID 是否正確。</div>`;
                }
                showModal(msg);
            });
        };
        document.getElementById('cancelModalBtn').onclick = function() {
            document.getElementById('mainModal').classList.remove('active');
        };
    } else if (type === 'accept-applicant' || type === 'reject-applicant') {
        const applicantId = btn.getAttribute('data-applicant-id');
        const tid = btn.getAttribute('data-team-id');
        const isAccept = type === 'accept-applicant';
        let html = `<div class="mb-2">確定要${isAccept ? '接受' : '拒絕'}這位申請者嗎？</div>
            <button type="button" class="btn btn-${isAccept ? 'success' : 'danger'}" id="confirmApplicantBtn">${isAccept ? '確認接受' : '確認拒絕'}</button>
            <button type="button" class="btn btn-secondary" id="cancelModalBtn">取消</button>`;
        showModal(html);

        document.getElementById('confirmApplicantBtn').onclick = function() {
            const formData = new FormData();
            formData.append('tid', tid);
            formData.append('applicant_id', applicantId);
            formData.append('status', isAccept ? 'accepted' : 'rejected');
            fetch('my_team.php?ajax=update-applicant-status', {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(r => {
                if (r.success) {
                    location.reload();
                } else {
                    showModal('<div class="text-danger">操作失敗，請稍後再試。</div>');
                }
            });
        };
        document.getElementById('cancelModalBtn').onclick = function() {
            document.getElementById('mainModal').classList.remove('active');
        };
    }
});

// 報名新競賽
document.getElementById('joinCompBtn')?.addEventListener('click', function() {
    const cid = document.getElementById('joinCompInput').value.trim();
    const tid = this.getAttribute('data-team-id');
    if (!cid) return;
    fetch('my_team.php?ajax=competition&cid=' + encodeURIComponent(cid))
        .then(res => res.json())
        .then(data => {
            if (!data.CID) {
                showModal('<div class="text-danger">找不到此競賽</div>');
            } else {
                // 檢查是否已經參加
                fetch('my_team.php?ajax=join-competition', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'cid=' + encodeURIComponent(cid) + '&tid=' + encodeURIComponent(tid)
                }).then(res => res.json()).then(r => {
                    if (r.already) {
                        showModal('<div class="text-danger">已經參加此競賽了!</div>');
                    } else if (r.success) {
                        showModal('<div class="text-success">報名成功！</div>');
                        setTimeout(() => location.reload(), 600);
                    } else if (r.error) {
                        showModal('<div class="text-danger">' + r.error + '</div>');
                    }
                });
            }
        });
});

document.getElementById('modalClose').onclick = function() {
    document.getElementById('mainModal').classList.remove('active');
};
document.getElementById('mainModal').onclick = function(e) {
    if (e.target === this) this.classList.remove('active');
};

let withdrawData = {};
let deleteData = {};
// 撤回邀請 modal
document.querySelectorAll('.open-modal[data-modal-type="withdraw-invitation"]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        withdrawData = {
            TeamID: btn.getAttribute('data-team-id'),
            InviterID: btn.getAttribute('data-inviter-id'),
            InviteeID: btn.getAttribute('data-invitee-id')
        };
        document.getElementById('withdrawInvitationModal').style.display = 'flex';
    });
});
document.getElementById('cancelWithdrawInvitation').onclick = function() {
    document.getElementById('withdrawInvitationModal').style.display = 'none';
};
document.getElementById('withdrawInvitationModalClose').onclick = function() {
    document.getElementById('withdrawInvitationModal').style.display = 'none';
};
document.getElementById('confirmWithdrawInvitation').onclick = function() {
    fetch('my_team.php?ajax=withdraw-invitation', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(withdrawData)
    })
    .then(res => res.text())
    .then(txt => {
        console.log('AJAX回傳:', txt);
        let data = {};
        try { data = JSON.parse(txt); } catch(e) { console.error('JSON解析失敗', e); }
        if(data.success){
            location.reload();
        }else{
            alert('撤回失敗');
        }
    });
};

// 刪除邀請 modal
document.querySelectorAll('.open-modal[data-modal-type="delete-invitation"]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        deleteData = {
            TeamID: btn.getAttribute('data-team-id'),
            InviterID: btn.getAttribute('data-inviter-id'),
            InviteeID: btn.getAttribute('data-invitee-id')
        };
        document.getElementById('deleteInvitationModal').style.display = 'flex';
    });
});
document.getElementById('cancelDeleteInvitation').onclick = function() {
    document.getElementById('deleteInvitationModal').style.display = 'none';
};
document.getElementById('deleteInvitationModalClose').onclick = function() {
    document.getElementById('deleteInvitationModal').style.display = 'none';
};
document.getElementById('confirmDeleteInvitation').onclick = function() {
    // AJAX 刪除邀請
    fetch('my_team.php?ajax=delete-invitation', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(deleteData)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            location.reload();
        }else{
            alert('刪除失敗');
        }
    });
};

