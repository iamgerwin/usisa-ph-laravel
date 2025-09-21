#!/bin/bash

# Create a temporary branch from the base
git checkout -b temp-clean-branch feature/ticket-86d07v1by-dime-gov-database-structure

# Cherry-pick each commit with cleaned messages
git cherry-pick --no-commit 688cb8e4f1b3f35c6b00052f011942e79d03c672
git commit -m "feat: add project progress resource configuration"

git cherry-pick --no-commit 931338e9991117b27b224a33aef8347267e4187d
git commit -m "feat: implement scraper job resource configuration"

git cherry-pick --no-commit 3e49dc653aea9555584b88642d1b536a668f9d10
git commit -m "feat: add source of funds resource implementation"

git cherry-pick --no-commit 9ebbad8de97c8b8a1669dd66d3b3723b3e22d6ef
git commit -m "feat: add project contractor resource configuration"

git cherry-pick --no-commit a42c61af90c2a28d13f0d7eeacb80dad7aa87e19
git commit -m "feat: implement implementing office resource"

git cherry-pick --no-commit ccf70e5da08bb4d13856fac67244597400bea63e
git commit -m "feat: add program resource configuration"

git cherry-pick --no-commit 4be13d4618cb275dad69b19d2c200aef986baea3
git commit -m "feat: add project resource implementation"

git cherry-pick --no-commit dad6d24940baff809933a0e35ea017d9f6cecff9
git commit -m "feat: implement project implementing office resource"

git cherry-pick --no-commit 0686e8a883aa839f63f03595c2337394b1b719dc
git commit -m "feat: add project source of funds resource configuration"

git cherry-pick --no-commit 5cc383253ca70cc5ca41c5132fcf854470c19c63
git commit -m "feat: implement Filament project resources configuration"

git cherry-pick --no-commit 8403d33b78dc1c95249335dff06e5b0e622e9f1e
git commit -m "feat: implement DIME data normalization with entity relationships"

git cherry-pick --no-commit bcd038fa782b665a7512f7f808a0da9dc40a26a0
git commit -m "fix: correct relationship column references in DIME data normalization"

git cherry-pick --no-commit cf3e9980f287256a9d2b7304d54518e292a5c056
git commit -m "feat: update DIME data normalization to use UUID relationships"

git cherry-pick --no-commit bd9609dd83d959427d80e89434528098ab598e47
git commit -m "fix: resolve ScraperJobStatus enum type error in Filament admin table"

git cherry-pick --no-commit cb4e1868cc563c8941b3674ee2933f1e6d327af7
git commit -m "Merge pull request #6 from iamgerwin/feature/ticket-86d07vv9d-normalize-dime-data"

git cherry-pick --no-commit 1120c80dad60b956b700dcd6ec37231be1a5661b
git commit -m "feat: fix Filament resources for Barangays, Cities, and Contractors"

git cherry-pick --no-commit c94c05e0af85a0c281ad593aff17698140d72bd5
git commit -m "feat: complete remaining Filament resources implementation"

git cherry-pick --no-commit 4b4e5f1b2cae9e0facb4f9241f83716639357188
git commit -m "fix: critical Filament namespace and structure issues"

git cherry-pick --no-commit 011e55daeecfdef3467765c4b097a0d358e3f380
git commit -m "fix: correct Filament v4 Schema structure for all resources"

git cherry-pick --no-commit 54011a8c482f62bdcb906cd4b4ea9b7ddc57223b
git commit -m "fix: correct Filament v4 component namespace imports"

echo "Commits cleaned successfully!"