# fundraising_platform 資料庫詳細說明

## 資料庫概述
`fundraising_platform` 是一個競賽組隊配對系統的資料庫，支援學生與管理員功能。學生可更新個人檔案、申請隊伍、管理邀請、查看隊伍歷史與評價；管理員可創建與審核競賽、管理分類、處理糾紛與黑名單、發布公告。以下為資料庫實體、對應頁面、問題與改進建議。

---

## 資料庫實體與屬性

### 1. User
- **屬性**: Account (PK), Password, Name
- **說明**: 儲存所有使用者（學生、管理員）的基本資訊。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - index.php: 登入（驗證 Account, Password）、註冊（插入 Account, Password, Name）。
  - update_profile.php: 更新 Name。
  - team_ratings.php: 顯示 Reviewer_Name, Reviewee_Name。

### 2. Blacklist
- **屬性**: SID (PK), Reason
- **說明**: 記錄被封鎖的學生及其原因。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - manage_blacklist.php: 新增、查看、移除黑名單記錄。

### 3. Announcement
- **屬性**: AnnouncementID (PK), Title, Content, Published_By
- **說明**: 儲存管理員發布的公告。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - post_announcement.php: 管理員發布公告（插入 Title, Content, Published_By）。
  - index.php: 首頁輪播顯示公告（查詢 Title, Content）。

### 4. Tag
- **屬性**: Tag (PK)
- **說明**: 計劃用於競賽標籤，但未實現。
- **使用情況**: 未使用。
- **對應頁面與 PHP**: 無。
- **備註**: 改用 Competition.Field 模擬分類（manage_categories.php）。

### 5. CompetitionRequireSkill
- **屬性**: Competition (CID), Skill
- **說明**: 記錄競賽所需的技能。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - competition_details.php: 顯示競賽技能需求。

### 6. TeamRequireSkill
- **屬性**: Team (TID), Skill
- **說明**: 計劃記錄隊伍所需技能，但未實現。
- **使用情況**: 未使用。
- **對應頁面與 PHP**: 無。
- **備註**: 因 TeamRecruitment.Required_Skills 缺失，無法實現技能匹配。

### 7. Award
- **屬性**: SID, Award_Title
- **說明**: 計劃記錄學生獲獎紀錄，但未實現。
- **使用情況**: 未使用。
- **對應頁面與 PHP**: 無。
- **備註**: 改用 Skill 表儲存（前綴 Award:，update_profile.php）。

### 8. Portfolio
- **屬性**: SID, Title
- **說明**: 計劃記錄學生作品集，但未實現。
- **使用情況**: 未使用。
- **對應頁面與 PHP**: 無。
- **備註**: 改用 Skill 表儲存（前綴 Portfolio:，update_profile.php）。

### 9. Skill
- **屬性**: SID, Skill
- **說明**: 儲存學生技能，模擬 Award 和 Portfolio。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - update_profile.php: 新增、更新、刪除技能（Skill），模擬 Award（Award: 前綴）、Portfolio（Portfolio: 前綴）。
  - apply_team.php: 原計劃用於技能匹配（因 TeamRecruitment.Required_Skills 缺失未實現）。

### 10. Student
- **屬性**: SID (PK)
- **說明**: 標識學生身份，SID 參考 User.Account。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - index.php: 註冊學生（插入 SID）。
  - apply_team.php, manage_invitations.php, team_history.php, team_ratings.php: 使用 SID 標識學生。

### 11. Administrator
- **屬性**: AID (PK)
- **說明**: 標識管理員身份，AID 參考 User.Account。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - index.php: 管理員登入（驗證 AID）。
  - admin.php, create_competition.php, approve_competition.php, manage_categories.php, manage_disputes.php, manage_blacklist.php, post_announcement.php: 限制管理員存取。

### 12. TeamDispute
- **屬性**: DisputeID (PK), ComplainantID, RespondentID, AdminID
- **說明**: 記錄隊伍糾紛及處理管理員。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - manage_disputes.php: 查看、處理糾紛。

### 13. Competition
- **屬性**: CID (PK), Name, Organizing_Units, Field, Registration_Deadline, Prize_Money, Eligibility_Requirements, Required_Number
- **說明**: 儲存競賽資訊。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - index.php: 顯示競賽清單（Name, Field 等）。
  - create_competition.php: 新增競賽（插入所有屬性，Organizing_Units 附加「待審核」）。
  - admin.php: 管理競賽（顯示 Name, Organizing_Units, Registration_Deadline）。
  - approve_competition.php: 移除「待審核」標記（更新 Organizing_Units）。
  - manage_categories.php: 管理分類（查詢 Field）。
  - competition_details.php: 顯示競賽詳情（所有屬性）。

### 14. InvitationList
- **屬性**: InvitationID (PK), TeamID, InviterID, InviteeID
- **說明**: 計劃記錄隊伍邀請，但未實現。
- **使用情況**: 未使用。
- **對應頁面與 PHP**: 無。
- **備註**: 改用 TeamMembershipHistory（Join_Date = NULL）模擬申請。

### 15. ApplicationList
- **屬性**: ApplicationID (PK), TeamID, ApplicantID
- **說明**: 計劃記錄隊伍申請，但未實現。
- **使用情況**: 未使用。
- **對應頁面與 PHP**: 無。
- **備註**: 改用 TeamMembershipHistory（Join_Date = NULL）模擬申請。

### 16. Team
- **屬性**: TID (PK), Team_Name, Leader, Competition
- **說明**: 儲存隊伍資訊，Leader 為 SID，Competition 為 CID。
- **使用情況**: TID, Team_Name, Leader 使用；Competition 未使用（資料庫中缺失）。
- **對應頁面與 PHP**:
  - apply_team.php: 顯示 Team_Name，申請隊伍（TID）。
  - manage_invitations.php: 過濾 Leader，管理申請（TID, Team_Name）。
  - team_history.php: 顯示隊伍歷史（TID, Team_Name）。
  - team_ratings.php: 顯示評價（TID, Team_Name）。
- **備註**: Competition 缺失，無法關聯隊伍與競賽。

### 17. Participation
- **屬性**: Competition (CID), Team (TID)
- **說明**: 計劃記錄隊伍參與的競賽，但未實現。
- **使用情況**: 未使用。
- **對應頁面與 PHP**: 無。
- **備註**: 因 Team.Competition 缺失，無法實現。

### 18. TeamMembershipHistory
- **屬性**: Team (TID), Member (SID), Join_Date, Leave_Date
- **說明**: 記錄隊伍成員歷史，模擬申請（Join_Date = NULL）。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - apply_team.php: 插入申請（Join_Date = NULL）。
  - manage_invitations.php: 接受（更新 Join_Date）或拒絕（刪除）申請。
  - team_history.php: 顯示隊伍歷史（Team, Member, Join_Date, Leave_Date）。

### 19. TeamRatings
- **屬性**: Team (TID), Reviewer (SID), Reviewee (SID), Rating, Comment
- **說明**: 記錄隊友評價。
- **使用情況**: 全數使用。
- **對應頁面與 PHP**:
  - team_ratings.php: 顯示評價（所有屬性）。

### 20. TeamRecruitment
- **屬性**: Team (TID), Required_Skills, Conditions
- **說明**: 記錄隊伍徵求，計劃包含技能需求和條件。
- **使用情況**: 僅 Team 使用；Required_Skills, Conditions 未使用（資料庫中缺失）。
- **對應頁面與 PHP**:
  - apply_team.php: 顯示徵求（Team, 查詢 TID）。
  - manage_invitations.php: 管理申請（Team）。
  - post_recruitment.php: 假設插入徵求（需確認）。
- **備註**: Required_Skills, Conditions 缺失，限制技能匹配和申請追蹤。

---

## 對應頁面與 PHP 檔案

以下列出所有頁面及其功能，標註檔案狀態（現有、新增、更新）。

### 學生頁面
1. **index.php** (現有)
   - 功能: 首頁，顯示公告（Announcement）、競賽（Competition），支援登入與註冊。
   - 實體: User, Announcement, Competition, Student, Administrator
2. **student.php** (更新)
   - 功能: 學生儀表板，側邊欄導航。
   - 實體: User, Student
3. **update_profile.php** (更新)
   - 功能: 更新姓名（User.Name）、技能、獲獎、作品集（Skill）。
   - 實體: User, Skill
4. **post_recruitment.php** (現有，未驗證)
   - 功能: 發布隊伍徵求，假設插入 TeamRecruitment。
   - 實體: TeamRecruitment, Team
   - 狀態: 需確認是否僅插入 Team（TID）。
5. **apply_team.php** (新增，多次更新)
   - 功能: 申請隊伍，顯示徵求清單，插入申請（TeamMembershipHistory, Join_Date = NULL）。
   - 實體: TeamRecruitment, Team, TeamMembershipHistory
   - 限制: 無 Required_Skills，無法技能匹配；無 Competition，僅顯示 Team_Name。
6. **manage_invitations.php** (新增，多次更新)
   - 功能: 管理申請（接受/拒絕，更新或刪除 TeamMembershipHistory）。
   - 實體: TeamRecruitment, Team, TeamMembershipHistory
   - 限制: 無 Conditions，申請狀態簡化；無 Competition，僅顯示 Team_Name。
7. **team_history.php** (現有)
   - 功能: 顯示隊伍歷史。
   - 實體: Team, TeamMembershipHistory
8. **team_ratings.php** (更新)
   - 功能: 顯示隊友評價，新增返回按鈕。
   - 實體: TeamRatings, Team, User

### 管理員頁面
1. **admin.php** (更新)
   - 功能: 競賽管理，顯示競賽清單，支援編輯與審核。
   - 實體: Competition
2. **create_competition.php** (更新)
   - 功能: 創建競賽，附加「待審核」標記（Organizing_Units）。
   - 實體: Competition
3. **approve_competition.php** (新增)
   - 功能: 批准競賽，移除「待審核」標記。
   - 實體: Competition
4. **manage_categories.php** (新增)
   - 功能: 管理競賽分類（Competition.Field）。
   - 實體: Competition
5. **manage_disputes.php** (現有)
   - 功能: 處理隊伍糾紛。
   - 實體: TeamDispute
6. **manage_blacklist.php** (現有)
   - 功能: 管理黑名單。
   - 實體: Blacklist
7. **post_announcement.php** (現有)
   - 功能: 發布公告。
   - 實體: Announcement

### 其他檔案
- **competition_details.php** (現有)
   - 功能: 顯示競賽詳情。
   - 實體: Competition, CompetitionRequireSkill
- **edit_competition.php** (現有)
   - 功能: 編輯競賽。
   - 實體: Competition
- **db_connect.php** (現有)
   - 功能: 資料庫連線（PDO）。
- **assets/css/style.css** (更新)
   - 功能: 樣式表，確保側邊欄一致，紅色登出按鈕。
- **assets/js/script.js** (現有)
   - 功能: 前端腳本（假設無變更）。

---

## 尚未解決的問題

1. **技能匹配功能失效**
   - 問題: apply_team.php 無法根據 Skill.Skill 和 TeamRecruitment.Required_Skills 過濾徵求，因 Required_Skills 缺失。
   - 影響: 學生無法快速找到符合技能的隊伍，降低組隊效率。
   - 檔案: apply_team.php

2. **申請追蹤簡化**
   - 問題: 無 ApplicationList 和 TeamRecruitment.Conditions，申請記錄於 TeamMembershipHistory（Join_Date = NULL），缺乏狀態管理。
   - 影響: 申請僅顯示「待審核」，無拒絕原因或歷史記錄，可能導致資料混亂。
   - 檔案: apply_team.php, manage_invitations.php

3. **競賽關聯缺失**
   - 問題: Team.Competition 缺失，apply_team.php 和 manage_invitations.php 無法顯示 Competition.Name，僅顯示 Team_Name。
   - 影響: 學生難以了解徵求對應的競賽，降低實用性。
   - 檔案: apply_team.php, manage_invitations.php

4. **分類功能受限**
   - 問題: 無 Tag 表，競賽分類依賴 Competition.Field（manage_categories.php），無法支援多標籤。
   - 影響: 競賽篩選簡化，影響使用者體驗。
   - 檔案: manage_categories.php

5. **post_recruitment.php 相容性未驗證**
   - 問題: post_recruitment.php 假設插入 TeamRecruitment，可能引用 Required_Skills 或 Conditions，引發錯誤。
   - 影響: 徵求發布可能失敗。
   - 檔案: post_recruitment.php

6. **密碼明文儲存**
   - 問題: User.Password 為明文，存在安全風險。
   - 影響: 使用者資料可能被竊。
   - 檔案: index.php

7. **隊伍領導者驗證**
   - 問題: manage_invitations.php 依賴 Team.Leader 過濾徵求，若 Leader 資料不完整，申請可能不顯示。
   - 影響: 申請管理功能不穩定。
   - 檔案: manage_invitations.php

8. **錯誤處理不足**
   - 問題: 無全面 try-catch，資料庫錯誤（如 PDOException）暴露給使用者。
   - 影響: 使用者體驗差，難以診斷問題。
   - 檔案: 所有 PHP 檔案

---

## 可改善的點

1. **恢復技能匹配**
   - 若可新增 TeamRecruitment.Required_Skills 或使用 TeamRequireSkill，apply_team.php 可過濾徵求：
     ```php
     WHERE EXISTS (SELECT 1 FROM Skill s WHERE s.SID = ? AND FIND_IN_SET(s.Skill, tr.Required_Skills))
     ```

2. **完善申請管理**
   - 新增 ApplicationList（ApplicationID, TeamID, ApplicantID, Status），取代 TeamMembershipHistory 的模擬方案，支援狀態（如「待審核」、「已接受」、「已拒絕」）。

3. **重建競賽關聯**
   - 新增 Team.Competition 或 TeamRecruitment.CID，apply_team.php 和 manage_invitations.php 可顯示 Competition.Name：
     ```php
     JOIN Competition c ON t.Competition = c.CID
     ```

4. **增強分類功能**
   - 新增 Tag 表，支援多標籤。manage_categories.php 可管理 Tag 並關聯 Competition：
     ```sql
     CREATE TABLE CompetitionTag (CompetitionID, Tag);
     ```

5. **驗證並更新 post_recruitment.php**
   - 確認僅插入 TeamRecruitment：
     ```php
     $stmt = $pdo->prepare("INSERT INTO TeamRecruitment (Team) VALUES (?)");
     $stmt->execute([$tid]);
     ```

6. **密碼加密**
   - index.php 使用 password_hash() 和 password_verify()：
     ```php
     $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
     ```

7. **改善錯誤處理**
   - 所有查詢包裝 try-catch：
     ```php
     try {
         $stmt->execute();
     } catch (PDOException $e) {
         $error = "操作失敗，請聯繫管理員";
     }
     ```

8. **驗證隊伍領導者**
   - 檢查 Team.Leader 是否為有效 SID，manage_invitations.php 增加：
     ```php
     WHERE t.Leader IS NOT NULL
     ```

9. **增強使用者體驗**
   - apply_team.php: 增加競賽篩選（若有 Competition）。
   - manage_invitations.php: 顯示申請理由（若有 Conditions）。
   - index.php: 改善競賽搜尋功能。

---

## 未使用的實體與屬性

### 未使用實體
1. **Tag**: Tag
   - 原因: 改用 Competition.Field 模擬分類。
2. **TeamRequireSkill**: Team, Skill
   - 原因: TeamRecruitment.Required_Skills 缺失，無法實現。
3. **Award**: SID, Award_Title
   - 原因: 改用 Skill 模擬（Award: 前綴）。
4. **Portfolio**: SID, Title
   - 原因: 改用 Skill 模擬（Portfolio: 前綴）。
5. **InvitationList**: InvitationID, TeamID, InviterID, InviteeID
   - 原因: 改用 TeamMembershipHistory 模擬。
6. **ApplicationList**: ApplicationID, TeamID, ApplicantID
   - 原因: 改用 TeamMembershipHistory 模擬。
7. **Participation**: Competition, Team
   - 原因: Team.Competition 缺失，無法實現。

### 未使用屬性
1. **Team.Competition**
   - 原因: 資料庫缺失，無法關聯隊伍與競賽。
   - 檔案: apply_team.php, manage_invitations.php
2. **TeamRecruitment.Required_Skills, Conditions**
   - 原因: 資料庫缺失，無法實現技能匹配和申請追蹤。
   - 檔案: apply_team.php, manage_invitations.php, post_recruitment.php

---

## 後續步驟

1. **提供 post_recruitment.php**
   - 確認插入邏輯，確保僅使用 TeamRecruitment.Team。
2. **分享資料庫結構**
   - 執行以下 SQL，分享結果：
     ```sql
     DESCRIBE TeamRecruitment;
     DESCRIBE Team;
     DESCRIBE TeamRatings;
     DESCRIBE User;
     ```
3. **測試部署**
   - 覆蓋 apply_team.php, team_ratings.php（C:\xampp\htdocs\fundraising_platform）。
   - 測試申請（apply_team.php）、評價（team_ratings.php）、管理邀請（manage_invitations.php）。
4. **新增錯誤處理**
   - 若需更新所有檔案，請告知。
5. **未來改進**
   - 若可修改資料庫，新增 Team.Competition, TeamRecruitment.Required_Skills, Conditions, ApplicationList, Tag。
   - 實作密碼加密（index.php）。

---

## 結論
- 大部分屬性已使用，僅 Tag、TeamRequireSkill、Award、Portfolio、InvitationList、ApplicationList、Participation、TeamMembers，以及 Team.Competition、TeamRecruitment.Required_Skills 和 TeamRecruitment.Conditions 未使用。
- 主要問題為技能匹配、申請追蹤、競賽關聯、分分類受限，以及 post_recruitment.php 未驗證。
- 可改善點包括恢復功能、完善資料庫、安全性和使用者體驗。
- 請提供 post_recruitment.php 或 DESCRIBE 結果以解決剩餘問題。

日期：2025年6月25日