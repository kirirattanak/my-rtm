# Roles & Permissions

## User Roles

| Role | Description |
|------|-------------|
| **Admin** | Full system access. Manages users, roles, and all projects. |
| **Project Manager** | Creates and manages projects, assigns members, manages sprints. |
| **Business Analyst** | Creates and manages business requirements. |
| **Developer** | Creates and manages technical requirements and tasks. |
| **Tester** | Creates test cases, logs test runs. |
| **Viewer** | Read-only access to projects they are members of. |

Roles are assigned globally (system-wide) and can be overridden per project.

---

## Permissions Matrix

### User Management (Admin only)

| Action | Admin | PM | BA | Dev | Tester | Viewer |
|--------|-------|----|----|-----|--------|--------|
| Invite users | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Assign global roles | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Deactivate users | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

### Projects

| Action | Admin | PM | BA | Dev | Tester | Viewer |
|--------|-------|----|----|-----|--------|--------|
| Create project | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edit project | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Archive project | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| View project | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manage project members | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

### Business Requirements

| Action | Admin | PM | BA | Dev | Tester | Viewer |
|--------|-------|----|----|-----|--------|--------|
| Create / Edit BR | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Approve BR | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Delete BR | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| View BR | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Comment on BR | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

### Technical Requirements

| Action | Admin | PM | BA | Dev | Tester | Viewer |
|--------|-------|----|----|-----|--------|--------|
| Create / Edit TR | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Delete TR | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| View TR | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Comment on TR | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

### Test Cases & Runs

| Action | Admin | PM | BA | Dev | Tester | Viewer |
|--------|-------|----|----|-----|--------|--------|
| Create / Edit test case | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ |
| Delete test case | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Log test run | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ |
| View test cases & runs | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### Capacity Planning

| Action | Admin | PM | BA | Dev | Tester | Viewer |
|--------|-------|----|----|-----|--------|--------|
| Create / manage sprints | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Create / edit tasks | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Update task status | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| View tasks & sprints | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### RTM & Reports

| Action | Admin | PM | BA | Dev | Tester | Viewer |
|--------|-------|----|----|-----|--------|--------|
| View RTM matrix | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Export RTM / reports | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
