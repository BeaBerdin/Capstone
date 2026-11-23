"""Berdin, Bea - Selenium unit tests (Appendix: Unit Testing).

Learning and Assessment Modules and Administrator Modules of PATHWISE.
Each test is one documented case (Case-001 ... Case-065). The data comes from
tests/Selenium/support/runtime.php on a disposable SQLite database.
"""

from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select

from conftest import fixture as F, login, page_text, url


# ---------------------------------------------------------------- helpers

def shows(driver, wait, text):
    """Wait until the page shows ``text``."""
    wait.until(lambda d: text in page_text(d))
    return True


def element_with_text(driver, text, tag="*"):
    return driver.find_element(By.XPATH, f"//{tag}[contains(normalize-space(.), {text!r})]")


def click(driver, element):
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", element)
    element.click()


def choose(driver, question_id, letter):
    """Select an answer on the Take Quiz page."""
    click(driver, driver.find_element(By.CSS_SELECTOR, f"input[name='question_{question_id}'][value='{letter}']"))


def submit_quiz(driver, wait, accept_warning=None):
    click(driver, driver.find_element(By.ID, "quizSubmitButton"))
    if accept_warning is not None:
        alert = wait.until(EC.alert_is_present())
        text = alert.text
        alert.accept() if accept_warning else alert.dismiss()
        return text


def answer_quiz(driver, wait, letters):
    """Answer Quiz 1 (questions 1-4) with ``letters`` (None = leave blank)."""
    for question_id, letter in zip(F("questions"), letters):
        if letter:
            choose(driver, question_id, letter)
    return submit_quiz(driver, wait, accept_warning=True if None in letters else None)


def accept_confirm(wait):
    wait.until(EC.alert_is_present()).accept()


def forged_post(driver, path):
    """Submit a POST from the browser to a URL the page does not offer."""
    driver.execute_script(
        "const f = document.createElement('form'); f.method = 'POST'; f.action = arguments[0];"
        "const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token';"
        "t.value = document.querySelector('input[name=_token]').value; f.appendChild(t);"
        "document.body.appendChild(f); f.submit();", url(path))


def fill(driver, name, value):
    field = driver.find_element(By.NAME, name)
    field.clear()
    field.send_keys(value)


# ============================================ LEARNING AND ASSESSMENT MODULES
# ---------------------------------------------- Study Lessons (Student)

def test_case_001_open_published_lesson(driver, wait, snap):
    login(driver, wait, "stu_lesson")
    driver.get(url(f"/learn/{F('courses', 'a')}"))
    click(driver, driver.find_element(By.XPATH, "//a[contains(@href, '/lesson/') and contains(., 'Start')]"))
    assert shows(driver, wait, "A variable stores a value that your program can use and change.")
    assert "Variables and Data Types" in page_text(driver)
    snap("browser-result", element_with_text(driver, "A variable stores a value", "p"))


def test_case_002_mark_lesson_complete(driver, wait, snap):
    login(driver, wait, "stu_complete")
    driver.get(url(f"/lesson/{F('lessons', 'a2')}"))
    click(driver, element_with_text(driver, "Mark as Complete", "button"))
    assert shows(driver, wait, "Lesson marked as completed.")
    assert "You have completed this lesson." in page_text(driver)
    snap("browser-result", element_with_text(driver, "Lesson completed", "h3"))


def test_case_003_unpublished_lesson_not_available(driver, wait, snap):
    login(driver, wait, "stu_lesson")
    driver.get(url(f"/lesson/{F('lessons', 'a3')}"))
    assert shows(driver, wait, "404")
    assert "This lesson is not published yet." not in page_text(driver)
    snap("browser-result")


def test_case_004_lesson_refused_without_enrollment(driver, wait, snap):
    login(driver, wait, "stu_outsider")
    driver.get(url(f"/lesson/{F('lessons', 'a1')}"))
    assert shows(driver, wait, "403")
    assert "You must have an active enrollment in this course" in page_text(driver)
    snap("browser-result")


def test_case_005_teacher_cannot_open_student_lesson(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/lesson/{F('lessons', 'a1')}"))
    assert shows(driver, wait, "403")
    assert "Unauthorized" in page_text(driver)
    snap("browser-result")


def test_case_006_preview_lesson_before_enrolling(driver, wait, snap):
    login(driver, wait, "stu_outsider")
    driver.get(url(f"/marketplace/{F('courses', 'p')}"))
    assert shows(driver, wait, "Preview Lesson")
    click(driver, driver.find_element(By.CSS_SELECTOR, "[data-preview-lesson]"))
    assert shows(driver, wait, "You are previewing this lesson. Enroll to access the full course.")
    text = page_text(driver)
    assert "A network connects devices so they can share data." in text
    assert "Enroll for Free" in text and "Mark as Complete" not in text
    snap("browser-result", driver.find_element(By.CSS_SELECTOR, "[data-preview-banner]"))


# ---------------------------------------------- Create Quiz (Teacher)

def test_case_007_teacher_creates_quiz_with_question(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher/lessons/{F('lessons', 'b1')}/quiz"))
    fill(driver, "title", "HTML Basics Quiz")
    fill(driver, "passing_score", "80")
    fill(driver, "new_question[question]", "Which tag creates a hyperlink?")
    for letter, text in zip("abcd", ["<p>", "<a>", "<img>", "<ul>"]):
        fill(driver, f"new_question[option_{letter}]", text)
    Select(driver.find_element(By.NAME, "new_question[correct_answer]")).select_by_value("B")
    fill(driver, "new_question[points]", "1")
    click(driver, driver.find_element(By.CSS_SELECTOR, "button[name=intent][value=save]"))
    assert shows(driver, wait, "All changes saved")
    saved = driver.find_elements(By.CSS_SELECTOR, "textarea[name^='questions[']")
    assert [q.get_attribute("value") for q in saved] == ["Which tag creates a hyperlink?"]
    snap("browser-result")


def test_case_008_publish_without_questions_refused(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher/lessons/{F('lessons', 'b2')}/quiz"))
    fill(driver, "title", "CSS Basics Quiz")
    fill(driver, "passing_score", "75")
    click(driver, driver.find_element(By.CSS_SELECTOR, "button[name=intent][value=publish]"))
    assert shows(driver, wait, "Add at least one complete question before publishing the quiz.")
    snap("browser-result")


def test_case_009_correct_answer_must_have_option(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher/lessons/{F('lessons', 'b3')}/quiz"))
    fill(driver, "title", "JavaScript Basics Quiz")
    fill(driver, "passing_score", "75")
    fill(driver, "new_question[question]", "Which keyword declares a variable?")
    fill(driver, "new_question[option_a]", "let")
    fill(driver, "new_question[option_b]", "echo")
    Select(driver.find_element(By.NAME, "new_question[correct_answer]")).select_by_value("C")
    fill(driver, "new_question[points]", "1")
    click(driver, driver.find_element(By.CSS_SELECTOR, "button[name=intent][value=save]"))
    assert shows(driver, wait, "Fill in option C for the selected correct answer.")
    snap("browser-result")


def test_case_010_teacher_publishes_quiz(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher/lessons/{F('lessons', 'b4')}/quiz"))
    click(driver, driver.find_element(By.CSS_SELECTOR, "button[name=intent][value=publish]"))
    assert shows(driver, wait, "Quiz published.")
    assert driver.find_elements(By.CSS_SELECTOR, "button[name=intent][value=unpublish]")
    snap("browser-result")


def test_case_011_quiz_of_published_course_is_read_only(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher/lessons/{F('lessons', 'a4')}/quiz"))
    assert shows(driver, wait, "Read only")
    assert driver.find_elements(By.CSS_SELECTOR, "button[name=intent]") == []
    snap("browser-result")


def test_case_012_student_cannot_open_quiz_builder(driver, wait, snap):
    login(driver, wait, "stu_lesson")
    driver.get(url(f"/teacher/lessons/{F('lessons', 'b1')}/quiz"))
    assert shows(driver, wait, "403")
    assert "Unauthorized" in page_text(driver)
    snap("browser-result")


# ---------------------------------------------- Take Quiz (Student)

def test_case_013_student_opens_published_quiz(driver, wait, snap):
    login(driver, wait, "stu_take")
    driver.get(url(f"/learn/{F('courses', 'a')}"))
    click(driver, driver.find_element(By.XPATH, "//a[contains(., 'Take Quiz')]"))
    assert shows(driver, wait, "Passing score")
    assert "75%" in page_text(driver)
    assert len(driver.find_elements(By.CSS_SELECTOR, "#quizForm fieldset")) == 4
    assert driver.find_element(By.ID, "quizSubmitButton").is_displayed()
    snap("browser-result")


def test_case_014_unpublished_quiz_not_available(driver, wait, snap):
    login(driver, wait, "stu_take")
    driver.get(url(f"/quiz/{F('quizzes', 'draft')}/take"))
    assert shows(driver, wait, "This quiz is not currently available to students.")
    snap("browser-result")


def test_case_015_quiz_refused_without_enrollment(driver, wait, snap):
    login(driver, wait, "stu_outsider")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    assert shows(driver, wait, "You must have an active enrollment in this course to access this quiz.")
    snap("browser-result")


def test_case_016_unanswered_question_warning(driver, wait, snap):
    login(driver, wait, "stu_take")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    for question_id, letter in zip(F("questions")[:3], "ABC"):
        choose(driver, question_id, letter)
    warning = submit_quiz(driver, wait, accept_warning=False)
    assert warning == "You have 1 unanswered question(s). Submit anyway?"
    assert "/take" in driver.current_url
    snap("browser-result", driver.find_element(By.ID, "quizSubmitButton"))


# ---------------------------------------------- Compute Quiz Score (Student)

def test_case_017_all_correct_scores_100(driver, wait, snap):
    login(driver, wait, "stu_full")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    answer_quiz(driver, wait, ["A", "B", "C", "D"])
    assert shows(driver, wait, "Latest score: 100.0%")
    assert "Quiz Passed" in page_text(driver)
    snap("browser-result", element_with_text(driver, "Latest score: 100.0%", "p"))


def test_case_018_score_equal_to_passing_score_passes(driver, wait, snap):
    login(driver, wait, "stu_pass75")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    answer_quiz(driver, wait, ["A", "B", "C", "A"])
    assert shows(driver, wait, "Latest score: 75.0%")
    assert "Quiz Passed" in page_text(driver)
    snap("browser-result", element_with_text(driver, "Latest score: 75.0%", "p"))


def test_case_019_score_below_passing_score_fails(driver, wait, snap):
    login(driver, wait, "stu_fail50")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    answer_quiz(driver, wait, ["A", "B", "A", "A"])
    assert shows(driver, wait, "Latest score: 50.0%")
    assert "Needs Retake" in page_text(driver)
    snap("browser-result", element_with_text(driver, "Latest score: 50.0%", "p"))


def test_case_020_each_question_counts_equally(driver, wait, snap):
    login(driver, wait, "stu_points")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    answer_quiz(driver, wait, ["A", "A", "A", "A"])  # only question 1 (5 points) is correct
    assert shows(driver, wait, "Latest score: 25.0%")
    snap("browser-result", element_with_text(driver, "Latest score: 25.0%", "p"))


def test_case_021_unanswered_question_counts_as_wrong(driver, wait, snap):
    login(driver, wait, "stu_blank")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    warning = answer_quiz(driver, wait, ["A", "B", "C", None])
    assert warning == "You have 1 unanswered question(s). Submit anyway?"
    assert shows(driver, wait, "Latest score: 75.0%")
    snap("browser-result", element_with_text(driver, "Latest score: 75.0%", "p"))


# ---------------------------------------------- Retake Quiz (Student)

def test_case_022_failed_quiz_offers_retake(driver, wait, snap):
    login(driver, wait, "stu_retake")
    driver.get(url(f"/learn/{F('courses', 'a')}"))
    assert shows(driver, wait, "Latest score: 50.0%")
    assert "Needs Retake" in page_text(driver)
    retake = driver.find_element(By.XPATH, "//a[contains(., 'Retake Quiz')]")
    snap("browser-result", retake)
    click(driver, retake)
    wait.until(EC.url_contains(f"/quiz/{F('quizzes', 'a')}/take"))


def test_case_023_retake_and_pass(driver, wait, snap):
    login(driver, wait, "stu_retake")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    answer_quiz(driver, wait, ["A", "B", "C", "D"])
    assert shows(driver, wait, "Latest score: 100.0%")
    assert "Quiz Passed" in page_text(driver)
    assert driver.find_elements(By.XPATH, "//a[contains(., 'Retake Quiz')]") == []
    snap("browser-result", element_with_text(driver, "Latest score: 100.0%", "p"))


def test_case_024_each_attempt_is_recorded(driver, wait, snap):
    login(driver, wait, "stu_retake2")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    answer_quiz(driver, wait, ["A", "B", "A", "A"])
    assert shows(driver, wait, "Latest score: 50.0%")
    login(driver, wait, "teacher")
    driver.get(url("/teacher/quiz-results"))
    row = lambda n: driver.find_elements(By.XPATH, f"//tr[contains(., 'Kyle Mendoza') and contains(., 'Attempt {n}')]")
    assert shows(driver, wait, "Kyle Mendoza") and row(1) and row(2)
    snap("browser-result", row(2)[0])


def test_case_025_passed_quiz_link_still_opens(driver, wait, snap):
    login(driver, wait, "stu_passed")
    driver.get(url(f"/learn/{F('courses', 'a')}"))
    assert shows(driver, wait, "Quiz Passed")
    assert driver.find_elements(By.XPATH, "//a[contains(., 'Retake Quiz')]") == []
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    assert wait.until(EC.visibility_of_element_located((By.ID, "quizSubmitButton")))
    snap("browser-result")


# ---------------------------------------------- View Progress (Student and Teacher)

def test_case_026_student_sees_course_progress(driver, wait, snap):
    login(driver, wait, "stu_prog")
    driver.get(url(f"/learn/{F('courses', 'a')}"))
    assert shows(driver, wait, "1 of 4 lessons")
    assert "25% complete" in page_text(driver)
    snap("browser-result", element_with_text(driver, "Course Progress", "p"))


def test_case_027_student_progress_updates_after_lesson(driver, wait, snap):
    login(driver, wait, "stu_prog")
    driver.get(url(f"/lesson/{F('lessons', 'a2')}"))
    click(driver, element_with_text(driver, "Mark as Complete", "button"))
    assert shows(driver, wait, "Lesson marked as completed.")
    driver.get(url(f"/learn/{F('courses', 'a')}"))
    assert shows(driver, wait, "2 of 4 lessons")
    assert "50% complete" in page_text(driver)
    snap("browser-result", element_with_text(driver, "Course Progress", "p"))


def test_case_028_teacher_sees_own_students_progress(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url("/teacher/student-progress"))
    assert shows(driver, wait, "Mark Ocampo")
    assert "Introduction to Programming" in page_text(driver)
    assert "Xian Aquino" not in page_text(driver)
    snap("browser-result", element_with_text(driver, "Mark Ocampo", "tr"))


def test_case_029_teacher_opens_one_student_progress(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher/course/{F('courses', 'a')}/student/{F('users', 'stu_prog', 'id')}/progress"))
    assert shows(driver, wait, "Mark Ocampo")
    assert "Introduction to Programming" in page_text(driver)
    assert "Ana Lopez" not in page_text(driver)
    snap("browser-result", element_with_text(driver, "Mark Ocampo", "tr"))


def test_case_030_student_cannot_open_teacher_progress(driver, wait, snap):
    login(driver, wait, "stu_prog")
    driver.get(url("/teacher/student-progress"))
    assert shows(driver, wait, "403")
    assert "Unauthorized" in page_text(driver)
    snap("browser-result")


def test_case_031_late_enrollee_activity_counts_without_due_date(driver, wait, snap):
    login(driver, wait, "stu_late")
    driver.get(url(f"/student/assignments/{F('activities', 'p')}"))
    fill(driver, "answer_text", "A router, a switch, three computers and a printer share one office network.")
    click(driver, driver.find_element(By.CSS_SELECTOR, "form[action$='/submit'] button[type=submit]"))
    assert shows(driver, wait, "Assignment submitted successfully.")
    driver.get(url(f"/learn/{F('courses', 'p')}"))
    assert shows(driver, wait, "1 of 3 lessons")
    snap("browser-result", element_with_text(driver, "Course Progress", "p"))


# ==================================================== ADMINISTRATOR MODULES
# ---------------------------------------------- Manage Course Categories

def test_case_032_add_category(driver, wait, snap):
    login(driver, wait, "edp")
    driver.get(url("/course-categories/create"))
    fill(driver, "name", "Data Science")
    fill(driver, "description", "Courses about data analysis.")
    click(driver, driver.find_element(By.CSS_SELECTOR, "form.sad-card button[type=submit]"))
    assert shows(driver, wait, "Category created successfully.")
    assert "Data Science" in page_text(driver)
    snap("browser-result")


def test_case_033_duplicate_category_refused(driver, wait, snap):
    login(driver, wait, "edp")
    driver.get(url("/course-categories/create"))
    fill(driver, "name", "Programming")
    click(driver, driver.find_element(By.CSS_SELECTOR, "form.sad-card button[type=submit]"))
    assert shows(driver, wait, "The name has already been taken.")
    snap("browser-result")


def test_case_034_edit_category(driver, wait, snap):
    login(driver, wait, "edp")
    driver.get(url(f"/course-categories/{F('categories', 'networking')}/edit"))
    fill(driver, "name", "Computer Networking")
    click(driver, driver.find_element(By.CSS_SELECTOR, "form.sad-card button[type=submit]"))
    assert shows(driver, wait, "Category updated successfully.")
    assert "Computer Networking" in page_text(driver)
    snap("browser-result")


def test_case_035_delete_unused_category(driver, wait, snap):
    login(driver, wait, "edp")
    driver.get(url("/course-categories"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/course-categories/{F('categories', 'unused')}'] button"))
    accept_confirm(wait)
    assert shows(driver, wait, "Category deleted successfully.")
    assert "Unused Category" not in page_text(driver)
    snap("browser-result")


def test_case_036_category_in_use_cannot_be_deleted(driver, wait, snap):
    login(driver, wait, "edp")
    driver.get(url("/course-categories"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/course-categories/{F('categories', 'programming')}'] button"))
    accept_confirm(wait)
    assert shows(driver, wait, '"Programming" cannot be deleted because it still has 7 courses.')
    snap("browser-result")


def test_case_037_dean_cannot_manage_categories(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/course-categories"))
    assert shows(driver, wait, "403")
    assert "Unauthorized" in page_text(driver)
    snap("browser-result")


# ---------------------------------------------- Review/Approve/Reject Courses

def test_case_038_approve_pending_course(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/courses"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/courses/{F('courses', 'c')}/approve'] button"))
    assert shows(driver, wait, "Course approved and published successfully.")
    assert driver.find_elements(By.CSS_SELECTOR, f"form[action$='/courses/{F('courses', 'c')}/approve']") == []
    snap("browser-result")


def test_case_039_reject_course_with_feedback(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/courses"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/courses/{F('courses', 'd')}/reject'] button"))
    prompt = wait.until(EC.alert_is_present())
    prompt.send_keys("Please add learning objectives and a final assessment.")
    prompt.accept()
    assert shows(driver, wait, "Course returned to the teacher for revision.")
    click(driver, driver.find_element(By.CSS_SELECTOR, "button[aria-label='Preview Mobile App Development']"))
    feedback = wait.until(EC.visibility_of_element_located((By.XPATH, "//dialog[@open]//p[contains(., 'Please add learning objectives')]")))
    assert "Rejected" in driver.find_element(By.CSS_SELECTOR, "dialog[open]").text
    snap("browser-result", feedback)


def test_case_040_reject_needs_five_character_feedback(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/courses"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/courses/{F('courses', 'd2')}/reject'] button"))
    prompt = wait.until(EC.alert_is_present())
    prompt.send_keys("Fix")
    prompt.accept()
    alert = wait.until(EC.alert_is_present())
    assert alert.text == "Please write at least 5 characters of feedback."
    alert.accept()
    assert driver.find_elements(By.CSS_SELECTOR, f"form[action$='/courses/{F('courses', 'd2')}/approve']")
    snap("browser-result", driver.find_element(By.CSS_SELECTOR, f"form[action$='/courses/{F('courses', 'd2')}/approve']"))


def test_case_041_dean_cannot_approve_other_department_course(driver, wait, snap):
    login(driver, wait, "dean2")
    driver.get(url("/courses"))
    assert shows(driver, wait, "Marketing Analytics")
    assert "Software Testing Basics" not in page_text(driver)
    forged_post(driver, f"/courses/{F('courses', 'd2')}/approve")
    assert shows(driver, wait, "403")
    snap("browser-result")


# ---------------------------------------------- Manage Certificates

def issue_certificate(driver, wait, student, number):
    driver.get(url("/certificates/create"))
    Select(driver.find_element(By.NAME, "student_id")).select_by_visible_text(student)
    Select(driver.find_element(By.NAME, "course_id")).select_by_value(str(F("courses", "f")))
    fill(driver, "certificate_number", number)
    driver.execute_script("document.getElementsByName('issued_date')[0].value = '2026-11-23';")
    Select(driver.find_element(By.NAME, "status")).select_by_value("issued")
    click(driver, driver.find_element(By.CSS_SELECTOR, "form[action$='/certificates'] button[type=submit]"))


def test_case_042_issue_certificate_after_passing_grades(driver, wait, snap):
    login(driver, wait, "dean")
    issue_certificate(driver, wait, "Nina Pascual", "PW-2026-0001")
    assert shows(driver, wait, "Certificate created successfully.")
    assert "PW-2026-0001" in page_text(driver)
    snap("browser-result")


def test_case_043_certificate_refused_for_failing_activity_grade(driver, wait, snap):
    login(driver, wait, "dean")
    issue_certificate(driver, wait, "Owen Quinto", "PW-2026-0002")
    assert shows(driver, wait, "The certificate cannot be issued yet: Final Project: score 60%, passing 75%")
    snap("browser-result")


def test_case_044_certificate_refused_while_activity_ungraded(driver, wait, snap):
    login(driver, wait, "dean")
    issue_certificate(driver, wait, "Pia Ramos", "PW-2026-0003")
    assert shows(driver, wait, "The certificate cannot be issued yet: Waiting for your teacher to grade: Final Project.")
    snap("browser-result")


def test_case_045_certificate_refused_before_course_completion(driver, wait, snap):
    login(driver, wait, "edp")
    issue_certificate(driver, wait, "Quin Salazar", "PW-2026-0004")
    assert shows(driver, wait, "A certificate is only available after the course has been completed.")
    snap("browser-result")


def test_case_046_student_cannot_manage_certificates(driver, wait, snap):
    login(driver, wait, "stu_cert_ok")
    driver.get(url("/certificates"))
    assert shows(driver, wait, "403")
    assert "Unauthorized" in page_text(driver)
    snap("browser-result")


# ---------------------------------------------- Moderate Course Reviews

def test_case_047_hide_review_with_reason(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/course-reviews"))
    review = F("reviews", "spam")
    driver.find_element(By.ID, f"hidden_reason_{review}").send_keys("Contains a spam link.")
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/course-reviews/{review}/hide'] button"))
    assert shows(driver, wait, "The review is now hidden.")
    assert "Contains a spam link." in page_text(driver)
    snap("browser-result")


def test_case_048_hide_review_requires_reason(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/course-reviews"))
    review = F("reviews", "fresh")
    reason = driver.find_element(By.ID, f"hidden_reason_{review}")
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/course-reviews/{review}/hide'] button"))
    assert reason.get_attribute("validationMessage") != ""
    assert "The review is now hidden." not in page_text(driver)
    snap("browser-result", reason)


def test_case_049_unhide_review(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/course-reviews"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/course-reviews/{F('reviews', 'hidden')}/unhide'] button"))
    accept_confirm(wait)
    assert shows(driver, wait, "The review is visible again.")
    snap("browser-result")


def test_case_050_dean_sees_only_own_department_reviews(driver, wait, snap):
    login(driver, wait, "dean2")
    driver.get(url("/course-reviews"))
    assert shows(driver, wait, "Business review from another department.")
    assert "Very clear lessons and helpful quizzes." not in page_text(driver)
    snap("browser-result")


# ---------------------------------------------- Request Student Removal (Teacher)

def open_removal_form(driver, student_key):
    student = F("users", student_key, "id")
    reason = driver.find_element(By.ID, f"removal_reason_{student}")
    click(driver, reason.find_element(By.XPATH, "./ancestor::details/summary"))
    return reason


def test_case_051_teacher_requests_student_removal(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher-courses/{F('courses', 'a')}/students"))
    reason = open_removal_form(driver, "stu_rm1")
    reason.send_keys("Student has been inactive for the whole term.")
    click(driver, reason.find_element(By.XPATH, "./following-sibling::button"))
    accept_confirm(wait)
    assert shows(driver, wait, "Student removal request submitted successfully.")
    driver.get(url("/teacher/student-removal-requests"))
    assert shows(driver, wait, "Rey Tan") and "Pending" in page_text(driver)
    snap("browser-result")


def test_case_052_duplicate_pending_request_refused(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher-courses/{F('courses', 'a')}/students"))
    reason = open_removal_form(driver, "stu_noquiz")
    reason.send_keys("Second request for the same student.")
    click(driver, reason.find_element(By.XPATH, "./following-sibling::button"))
    accept_confirm(wait)
    assert shows(driver, wait, "A pending removal request already exists for this student in this course.")
    snap("browser-result")


def test_case_053_removal_reason_needs_ten_characters(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher-courses/{F('courses', 'a')}/students"))
    reason = open_removal_form(driver, "stu_rm3")
    reason.send_keys("Absent")
    click(driver, reason.find_element(By.XPATH, "./following-sibling::button"))
    assert reason.get_attribute("validationMessage") != ""
    assert "Student removal request submitted successfully." not in page_text(driver)
    snap("browser-result", reason)


# ---------------------------------------------- Review Student Removal Requests

def review_form(driver, key):
    return f"form[action$='/admin/student-removal-requests/{F('removal', key)}/review']"


def test_case_054_approve_removal_request(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/admin/student-removal-requests"))
    click(driver, driver.find_element(By.CSS_SELECTOR, review_form(driver, "rm2") + " button[value=approved]"))
    accept_confirm(wait)
    assert shows(driver, wait, "Removal request approved successfully.")
    snap("browser-result")
    login(driver, wait, "stu_rm2")
    driver.get(url(f"/learn/{F('courses', 'a')}"))
    assert shows(driver, wait, "403")


def test_case_055_reject_removal_request_with_note(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/admin/student-removal-requests"))
    form = review_form(driver, "rm3")
    driver.find_element(By.CSS_SELECTOR, form + " textarea[name=review_note]").send_keys("Student will move to the correct section.")
    click(driver, driver.find_element(By.CSS_SELECTOR, form + " button[value=rejected]"))
    accept_confirm(wait)
    assert shows(driver, wait, "Removal request rejected successfully.")
    assert "Student will move to the correct section." in page_text(driver)
    snap("browser-result")


def test_case_056_other_department_dean_cannot_see_request(driver, wait, snap):
    login(driver, wait, "dean2")
    driver.get(url("/admin/student-removal-requests"))
    assert shows(driver, wait, "Instructor requests from your department will appear here.")
    assert "Vic Yap" not in page_text(driver)
    snap("browser-result")


def test_case_057_teacher_cannot_review_removal_requests(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url("/admin/student-removal-requests"))
    assert shows(driver, wait, "403")
    assert "Unauthorized" in page_text(driver)
    snap("browser-result")


# ---------------------------------------------- Manage AI Recommendations

def generate_recommendation(driver, student):
    driver.get(url("/ai-recommendations/create"))
    Select(driver.find_element(By.NAME, "student_id")).select_by_visible_text(student)
    click(driver, driver.find_element(By.CSS_SELECTOR, "form[action$='/ai-recommendations'] button[type=submit]"))


def test_case_058_generate_ai_recommendation(driver, wait, snap):
    login(driver, wait, "dean")
    generate_recommendation(driver, "Uma Wong")
    assert shows(driver, wait, "AI recommendation generated successfully.")
    row = driver.find_element(By.XPATH, "//tr[contains(., 'Uma Wong')]")
    assert "Advanced Algorithms" in row.text and "90" in row.text
    snap("browser-result", row)


def test_case_059_recommendation_needs_quiz_results(driver, wait, snap):
    login(driver, wait, "edp")
    generate_recommendation(driver, "Vic Yap")
    assert shows(driver, wait, "No eligible published quiz results were found for this student.")
    snap("browser-result")


def test_case_060_delete_ai_recommendation(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/ai-recommendations"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/ai-recommendations/{F('recommendations', 'old')}'] button"))
    accept_confirm(wait)
    assert shows(driver, wait, "Recommendation deleted successfully.")
    assert "Old recommendation to delete" not in page_text(driver)
    snap("browser-result")


def test_case_061_student_cannot_manage_ai_recommendations(driver, wait, snap):
    login(driver, wait, "stu_ai")
    driver.get(url("/ai-recommendations"))
    assert shows(driver, wait, "403")
    assert "Unauthorized" in page_text(driver)
    snap("browser-result")


# ---------------------------------------------- View Student Progress Reports

def test_case_062_dean_views_student_progress(driver, wait, snap):
    login(driver, wait, "dean")
    driver.get(url("/student-progress"))
    assert shows(driver, wait, "Mark Ocampo")
    assert "Introduction to Programming" in page_text(driver)
    snap("browser-result", element_with_text(driver, "Mark Ocampo", "tr"))


def test_case_063_dean_sees_only_own_department_progress(driver, wait, snap):
    login(driver, wait, "dean2")
    driver.get(url("/student-progress"))
    assert shows(driver, wait, "Xian Aquino")
    assert "Mark Ocampo" not in page_text(driver)
    snap("browser-result", element_with_text(driver, "Xian Aquino", "tr"))


def test_case_064_edp_views_reports(driver, wait, snap):
    login(driver, wait, "edp")
    driver.get(url("/super-admin/reports"))
    assert shows(driver, wait, "Reports & Analytics")
    snap("browser-result")


def test_case_065_teacher_cannot_open_admin_progress_reports(driver, wait, snap):
    login(driver, wait, "teacher")
    driver.get(url("/student-progress"))
    assert shows(driver, wait, "403")
    assert "Unauthorized" in page_text(driver)
    snap("browser-result")
