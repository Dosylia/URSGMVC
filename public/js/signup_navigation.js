function showStep(index) {
    const steps = document.querySelectorAll(".form-step");
    steps.forEach((step, i) => {
        step.classList.toggle("active", i === index);
    });

    // Hide prev on first step
    prevBtn.style.display = index === 0 ? "none" : "inline-block";

    // Hide next on last step
    nextBtn.style.display = index === steps.length - 1 ? "none" : "inline-block";
}


document.addEventListener("DOMContentLoaded",function(){

    const steps = document.querySelectorAll(".form-step");
    const nextBtn = document.getElementById("nextBtn");
    const prevBtn = document.getElementById("prevBtn");
    let currentStep = 0;

    nextBtn.addEventListener("click", () => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);
        }
    });

    prevBtn.addEventListener("click", () => {
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    });

    // Initialize first step
    showStep(currentStep);
})