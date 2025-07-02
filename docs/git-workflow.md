 # Git Workflow & Branching Strategy

**Date:** 2025-01-24 16:00:00  
**Project:** Demo51 - Livestock Management System  
**Status:** Implementation Guide

## 🎯 Overview

Dokumen ini menjelaskan workflow Git yang terstruktur dan rapi untuk project Demo51, menggantikan workflow saat ini yang langsung push ke master.

## 🌿 Branch Strategy

### Main Branches

#### 1. **main/master** (Production)

-   **Purpose**: Kode yang siap production
-   **Protection**:
    -   Require pull request reviews
    -   Require status checks to pass
    -   Restrict pushes
-   **Merge**: Hanya dari `develop` atau `hotfix/*`

#### 2. **develop** (Development)

-   **Purpose**: Integration branch untuk semua fitur
-   **Protection**:
    -   Require pull request reviews
    -   Require status checks to pass
-   **Merge**: Dari `feature/*`, `bugfix/*`, `hotfix/*`

### Supporting Branches

#### 3. **feature/\*** (Feature Development)

-   **Naming**: `feature/JIRA-123-user-company-mapping`
-   **Purpose**: Development fitur baru
-   **Base**: `develop`
-   **Merge**: Ke `develop` via Pull Request

#### 4. **bugfix/\*** (Bug Fixes)

-   **Naming**: `bugfix/JIRA-456-form-close-issue`
-   **Purpose**: Perbaikan bug
-   **Base**: `develop`
-   **Merge**: Ke `develop` via Pull Request

#### 5. **hotfix/\*** (Hot Fixes)

-   **Naming**: `hotfix/JIRA-789-critical-security-fix`
-   **Purpose**: Perbaikan kritis untuk production
-   **Base**: `main/master`
-   **Merge**: Ke `main/master` dan `develop` via Pull Request

#### 6. **release/\*** (Release Preparation)

-   **Naming**: `release/v1.2.0`
-   **Purpose**: Persiapan release
-   **Base**: `develop`
-   **Merge**: Ke `main/master` dan `develop`

## 📝 Commit Message Convention

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

-   **feat**: New feature
-   **fix**: Bug fix
-   **docs**: Documentation changes
-   **style**: Code style changes (formatting, etc.)
-   **refactor**: Code refactoring
-   **test**: Adding or updating tests
-   **chore**: Maintenance tasks

### Examples

```bash
# Feature
feat(user): add company_id to User model for performance improvement

# Bug fix
fix(company): resolve form close functionality issue

# Documentation
docs(workflow): add Git workflow documentation

# Refactor
refactor(base): optimize company_id lookup in BaseModel

# Test
test(user): add unit tests for company relationship

# Chore
chore(deps): update Laravel to version 10.35
```

## 🔄 Workflow Process

### 1. **Feature Development**

```bash
# 1. Start from develop
git checkout develop
git pull origin develop

# 2. Create feature branch
git checkout -b feature/JIRA-123-user-company-mapping

# 3. Make changes and commit
git add .
git commit -m "feat(user): add company_id field to User model"

# 4. Push feature branch
git push origin feature/JIRA-123-user-company-mapping

# 5. Create Pull Request to develop
# - Title: "feat: Add company_id to User model for performance improvement"
# - Description: Include JIRA ticket, changes, testing notes
```

### 2. **Bug Fix Process**

```bash
# 1. Start from develop
git checkout develop
git pull origin develop

# 2. Create bugfix branch
git checkout -b bugfix/JIRA-456-form-close-issue

# 3. Fix the bug and commit
git add .
git commit -m "fix(company): resolve form close functionality issue"

# 4. Push bugfix branch
git push origin bugfix/JIRA-456-form-close-issue

# 5. Create Pull Request to develop
```

### 3. **Release Process**

```bash
# 1. Create release branch from develop
git checkout develop
git pull origin develop
git checkout -b release/v1.2.0

# 2. Update version numbers, changelog
# 3. Commit release preparation
git commit -m "chore(release): prepare v1.2.0 release"

# 4. Create Pull Request to main/master
# 5. After approval, merge to main/master
# 6. Tag the release
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0

# 7. Merge back to develop
git checkout develop
git merge release/v1.2.0
git push origin develop

# 8. Delete release branch
git branch -d release/v1.2.0
git push origin --delete release/v1.2.0
```

### 4. **Hotfix Process**

```bash
# 1. Create hotfix branch from main/master
git checkout main/master
git pull origin main/master
git checkout -b hotfix/JIRA-789-critical-security-fix

# 2. Fix the critical issue
git commit -m "fix(security): patch critical security vulnerability"

# 3. Create Pull Request to main/master
# 4. After approval, merge to main/master
# 5. Tag the hotfix
git tag -a v1.1.1 -m "Hotfix v1.1.1"

# 6. Merge to develop
git checkout develop
git merge hotfix/JIRA-789-critical-security-fix
git push origin develop

# 7. Delete hotfix branch
git branch -d hotfix/JIRA-789-critical-security-fix
```

## 🛡️ Branch Protection Rules

### Main/Master Branch

-   ✅ Require pull request reviews before merging
-   ✅ Require status checks to pass before merging
-   ✅ Restrict pushes that create files larger than 100 MB
-   ✅ Require branches to be up to date before merging
-   ✅ Restrict pushes to matching branches

### Develop Branch

-   ✅ Require pull request reviews before merging
-   ✅ Require status checks to pass before merging
-   ✅ Restrict pushes to matching branches

## 📋 Pull Request Template

### Feature PR Template

```markdown
## 🎯 Feature Description

Brief description of the feature

## 🔗 Related Issues

-   Closes #JIRA-123
-   Related to #JIRA-124

## 📝 Changes Made

-   [ ] Added new feature X
-   [ ] Updated component Y
-   [ ] Added tests for Z

## 🧪 Testing

-   [ ] Unit tests pass
-   [ ] Integration tests pass
-   [ ] Manual testing completed
-   [ ] Cross-browser testing (if applicable)

## 📸 Screenshots (if applicable)

Add screenshots here

## ✅ Checklist

-   [ ] Code follows project style guidelines
-   [ ] Self-review completed
-   [ ] Documentation updated
-   [ ] No console errors
-   [ ] Performance impact assessed
```

### Bugfix PR Template

```markdown
## 🐛 Bug Description

Description of the bug and its impact

## 🔗 Related Issues

-   Fixes #JIRA-456

## 🔍 Root Cause

Analysis of what caused the bug

## 🛠️ Solution

Description of the fix implemented

## 🧪 Testing

-   [ ] Bug reproduction steps tested
-   [ ] Fix verified
-   [ ] Regression testing completed
-   [ ] No new bugs introduced

## ✅ Checklist

-   [ ] Code follows project style guidelines
-   [ ] Self-review completed
-   [ ] Documentation updated
-   [ ] No console errors
```

## 🚀 Migration Plan

### Phase 1: Setup (Week 1)

1. **Create develop branch**

    ```bash
    git checkout -b develop
    git push origin develop
    ```

2. **Set up branch protection rules**

    - Configure GitHub repository settings
    - Set up required reviewers
    - Configure status checks

3. **Update documentation**
    - Create this workflow guide
    - Update team on new process

### Phase 2: Training (Week 2)

1. **Team training session**

    - Git workflow overview
    - Branch naming conventions
    - Commit message standards
    - Pull request process

2. **Practice with small features**
    - Start with minor bug fixes
    - Gradually move to features

### Phase 3: Full Implementation (Week 3+)

1. **All new work uses feature branches**
2. **Regular code reviews**
3. **Automated testing integration**
4. **Release management**

## 📊 Benefits

### Before (Current State)

-   ❌ Direct pushes to master
-   ❌ Inconsistent commit messages
-   ❌ Merge conflicts
-   ❌ No code review process
-   ❌ Difficult to track changes
-   ❌ Risk of breaking production

### After (Proposed State)

-   ✅ Structured branching strategy
-   ✅ Consistent commit messages
-   ✅ Code review process
-   ✅ Clear change tracking
-   ✅ Safe production deployments
-   ✅ Better collaboration
-   ✅ Easier debugging and rollbacks

## 🔧 Tools & Automation

### Recommended Tools

1. **GitHub Actions** - CI/CD pipeline
2. **Husky** - Git hooks for commit message validation
3. **Commitizen** - Interactive commit message creation
4. **Conventional Changelog** - Automatic changelog generation

### Git Hooks Setup

```bash
# Install Husky
npm install --save-dev husky

# Setup commit message hook
npx husky add .husky/commit-msg 'npx --no -- commitlint --edit "$1"'
```

## 📚 Additional Resources

-   [Conventional Commits](https://www.conventionalcommits.org/)
-   [Git Flow](https://nvie.com/posts/a-successful-git-branching-model/)
-   [GitHub Flow](https://guides.github.com/introduction/flow/)
-   [Pull Request Best Practices](https://github.com/thoughtbot/guides/tree/master/code-review)

## 📞 Support

Untuk pertanyaan atau bantuan implementasi workflow ini, silakan hubungi:

-   **Lead Developer**: [Contact Info]
-   **DevOps Team**: [Contact Info]
-   **Documentation**: [Wiki Link]
