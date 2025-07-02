#!/bin/bash

# Git Workflow Setup Script for Demo51
# This script sets up the new Git workflow structure

echo "🚀 Setting up Git Workflow for Demo51..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    print_error "Not in a git repository. Please run this script from the project root."
    exit 1
fi

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
print_status "Current branch: $CURRENT_BRANCH"

# Step 1: Create develop branch
print_status "Step 1: Creating develop branch..."
if git show-ref --verify --quiet refs/heads/develop; then
    print_warning "Develop branch already exists. Switching to it..."
    git checkout develop
    git pull origin develop
else
    print_status "Creating develop branch from current branch..."
    git checkout -b develop
    git push -u origin develop
fi

# Step 2: Set up Git hooks
print_status "Step 2: Setting up Git hooks..."
if [ -f "package.json" ]; then
    print_status "Installing Husky and commit hooks..."
    npm install --save-dev husky commitizen cz-conventional-changelog @commitlint/cli @commitlint/config-conventional
    
    # Initialize Husky
    npx husky install
    
    # Add commit-msg hook
    npx husky add .husky/commit-msg 'npx --no -- commitlint --edit "$1"'
    
    # Add pre-commit hook
    npx husky add .husky/pre-commit 'npm run lint'
    
    print_success "Git hooks installed successfully"
else
    print_warning "package.json not found. Skipping Git hooks setup."
fi

# Step 3: Create initial commit with new workflow
print_status "Step 3: Creating initial workflow commit..."
git add .
git commit -m "chore(workflow): setup new Git workflow and branching strategy

- Add Git workflow documentation
- Setup branch protection templates
- Configure commit message validation
- Add pull request templates
- Setup development branch structure"

# Step 4: Push changes
print_status "Step 4: Pushing changes..."
git push origin develop

# Step 5: Create feature branch example
print_status "Step 5: Creating example feature branch..."
git checkout -b feature/example-workflow-setup
git push -u origin feature/example-workflow-setup

# Step 6: Switch back to develop
git checkout develop

print_success "✅ Git workflow setup completed!"
echo ""
echo "📋 Next Steps:"
echo "1. Configure branch protection rules in GitHub:"
echo "   - Go to Settings > Branches"
echo "   - Add rule for 'master' and 'develop'"
echo "   - Enable 'Require pull request reviews'"
echo "   - Enable 'Require status checks to pass'"
echo ""
echo "2. Team Training:"
echo "   - Review docs/git-workflow.md"
echo "   - Practice with feature branches"
echo "   - Use conventional commit messages"
echo ""
echo "3. Start Using New Workflow:"
echo "   - Create feature branches for new work"
echo "   - Use Pull Requests for code review"
echo "   - Follow commit message conventions"
echo ""
echo "📚 Documentation: docs/git-workflow.md"
echo "🔧 Tools: npm run commit (for interactive commits)" 