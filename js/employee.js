const skills = [];

document.getElementById('addSkill').addEventListener('click', function() {
    const skillInput = document.getElementById('skills');
    const skill = skillInput.value.trim();

    if (skill && !skills.includes(skill)) {
        skills.push(skill);
        updateSkillsList();
        skillInput.value = '';
        validateForm();
    }
});

function addSuggestedSkill(skill) {
    if (!skills.includes(skill)) {
        skills.push(skill);
        updateSkillsList();
        validateForm();
    }
}

function updateSkillsList() {
    const skillsList = document.getElementById('skillsList');
    skillsList.innerHTML = '';

    skills.forEach(skill => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary skill-badge';
        badge.textContent = skill;

        const removeButton = document.createElement('button');
        removeButton.className = 'btn-close btn-close-white ms-2';
        removeButton.addEventListener('click', function() {
            const index = skills.indexOf(skill);
            if (index !== -1) {
                skills.splice(index, 1);
                updateSkillsList();
                validateForm();
            }
        });

        badge.appendChild(removeButton);
        skillsList.appendChild(badge);
    });

    document.getElementById('skillsData').value = skills.join(', ');
}

function validateForm() {
    const errorMessage = document.getElementById('errorMessage');
    const form = document.getElementById('employeeForm');

    // Validate that at least one skill is added or a file is uploaded
    if (skills.length === 0 && !document.getElementById('cv').files.length) {
        errorMessage.classList.remove('d-none');
        form.querySelector('button[type="submit"]').disabled = true;
    } else {
        errorMessage.classList.add('d-none');
        form.querySelector('button[type="submit"]').disabled = false;
    }
}

document.getElementById('atClient').addEventListener('change', function() {
    const companyField = document.getElementById('companyField');
    companyField.style.display = this.checked ? 'block' : 'none';
});