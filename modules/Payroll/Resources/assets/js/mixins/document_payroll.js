import {getDiffInDays, getArrayStartEndDate} from '../helpers/functions'

export const documentPayrollMixin = {
    data() {
        return {
        }
    },
    methods: {
        // prima de servicio
        clickAddServiceBonus(){
            
            const salary_validation = this.salaryValidation()
            if(!salary_validation.success) return this.$message.warning(salary_validation.message)

            this.form.accrued.service_bonus.push({
                quantity :  0,
                payment :  0,
                paymentNS :  undefined,
            })

        },
        changePaymentNSServiceBonus(index){
            this.calculateTotal()
        },
        changePaymentServiceBonus(index){
            this.calculateTotal()
        },
        changeQuantityServiceBonus(index){
            
            this.setPaymentServiceBonus(index)
            this.calculateTotal()

        },
        setPaymentServiceBonus(index) {
            // obtener salario y subsidio
            let salario = parseFloat(this.form.accrued.total_base_salary || 0)
            let subsidio = parseFloat(this.form.accrued.transportation_allowance || 0)

            // días para la prima (input del usuario)
            let diasPrima = parseFloat(this.form.accrued.service_bonus[index].quantity || 0)

            if (diasPrima < 1 || diasPrima > 360) {
                this.form.accrued.service_bonus[index].payment = 0
                this.$message.warning('La prima de servicios debe calcularse entre 1 y 360 días.')
                return
            }

            // sumar conceptos remunerativos adicionales (valores originales)
            const extras = parseFloat(this.form.accrued.total_extra_hours || 0)
            const salaryBonuses = _.sumBy(this.form.accrued.bonuses || [], b => parseFloat(b.salary_bonus || 0))
            const otherSalaryConcepts = _.sumBy(this.form.accrued.other_concepts || [], o => parseFloat(o.salary_concept || 0))
            const commissions = _.sumBy(this.form.accrued.commissions || [], c => parseFloat(c.commission || 0))
            const epctvSalary = _.sumBy(this.form.accrued.epctv_bonuses || [], e => parseFloat(e.paymentS || 0) + parseFloat(e.salary_food_payment || 0))

            // Política: redondear cada componente a peso antes de sumar (para reproducir informe legacy)
            const components = [
                salario, subsidio, extras, salaryBonuses, otherSalaryConcepts, commissions, epctvSalary
            ].map(v => Math.round(parseFloat(v || 0)))

            const baseRemunerativaRounded = components.reduce((a,b) => a + b, 0)
            const baseRemunerativa = salario + subsidio + extras + salaryBonuses + otherSalaryConcepts + commissions + epctvSalary

            // Elegir factor: legacy 0.0833 para 30 días (opción para igualar tabla), si no usar dias/360
            const useLegacyFactor = true
            const factor = (useLegacyFactor && diasPrima === 30) ? 0.0833 : (diasPrima / 360)

            // calcular prima (dos variantes para comparar)
            const primaExact = (baseRemunerativa * diasPrima) / 360
            const primaLegacy = baseRemunerativaRounded * factor

            // resultado que se guardará (ajustar aquí si se quiere usar exacto vs legacy)
            const prima = this.roundNumber(primaLegacy, 2)

            // LOG detallado para depuración
            // try {
            //     console.group(`Prima de servicios - índice ${index}`)
            //     console.log('salario (total_base_salary):', salario)
            //     console.log('subsidio (transportation_allowance):', subsidio)
            //     console.log('extras (total_extra_hours):', extras)
            //     console.log('salaryBonuses (bonuses.salary_bonus):', salaryBonuses)
            //     console.log('otherSalaryConcepts (other_concepts.salary_concept):', otherSalaryConcepts)
            //     console.log('commissions (commissions.commission):', commissions)
            //     console.log('epctvSalary (epctv_bonuses paymentS + salary_food_payment):', epctvSalary)
            //     console.log('baseRemunerativa (sin redondear):', baseRemunerativa)
            //     console.log('baseRemunerativaRounded (componentes a peso):', baseRemunerativaRounded)
            //     console.log('diasPrima (días a liquidar):', diasPrima)
            //     console.log('factor usado:', factor)
            //     console.log('prima exacta (base * dias/360):', primaExact)
            //     console.log('prima legacy (baseRounded * factor):', primaLegacy)
            //     console.log('prima final (guardada):', prima)
            //     console.groupEnd()
            // } catch (e) {
            //     console.log('[Prima] datos:', { salario, subsidio, extras, salaryBonuses, otherSalaryConcepts, commissions, epctvSalary, baseRemunerativa, baseRemunerativaRounded, diasPrima, factor, prima })
            // }

            // almacenar resultado (paymentNS según estructura actual)
            this.form.accrued.service_bonus[index].paymentNS = prima
        },
        clickCancelServiceBonus(index){
            this.form.accrued.service_bonus.splice(index, 1)
            this.calculateTotal()
        },
        recalculateServiceBonus(){

            this.form.accrued.service_bonus.forEach((element, index) => {
                this.setPaymentServiceBonus(index)
            })

        },
        // prima de servicio

        // cesantias
        clickAddSeverance(){

            const salary_validation = this.salaryValidation()
            if(!salary_validation.success) return this.$message.warning(salary_validation.message)

            this.form.accrued.severance.push({
                payment :  0,
                percentage :  0,
                interest_payment :  0,
            })

        },
        clickCancelSeverance(index){
            this.form.accrued.severance.splice(index, 1)
            this.calculateTotal()
        },
        calculateInterestPayment(index){
            this.form.accrued.severance[index].interest_payment = Math.round(this.form.accrued.severance[index].payment * this.percentageToFactor(this.form.accrued.severance[index].percentage))
            this.calculateTotal()
        },
        // cesantias

        // bonificaciones
        clickAddBonuses(){

            this.form.accrued.bonuses.push({
                salary_bonus :  undefined,
                non_salary_bonus :  undefined,
            })

        },
        clickCancelBonuses(index){
            this.form.accrued.bonuses.splice(index, 1)
            this.calculateTotal()
        },
        changeSalaryBonus(index){
            this.calculateTotal()
        },
        // bonificaciones

        // vacaciones disfrutadas
        clickAddCommonVacation(){

            const salary_validation = this.salaryValidation()
            if(!salary_validation.success) return this.$message.warning(salary_validation.message)

            const quantity = 1
            const date_range = this.getStartEndDateRange(quantity)

            this.form.accrued.common_vacation.push({
                start_date : date_range[0],
                end_date : date_range[1],
                quantity :  quantity,
                payment :  0,
                start_end_date: date_range
            })

        },
        getStartEndDateRange(quantity){
            return [
                moment().format('YYYY-MM-DD'),
                moment().add(quantity, 'days').format('YYYY-MM-DD')
            ]
        }, 
        clickCancelCommonVacation(index){
            this.form.accrued.common_vacation.splice(index, 1)
            this.calculateTotal()
        },
        changeCommonVacationStartEndDate(index){
            
            const start_end_date = this.form.accrued.common_vacation[index].start_end_date
            const start_date = start_end_date[0]
            const end_date = start_end_date[1]

            this.form.accrued.common_vacation[index].start_date = start_date
            this.form.accrued.common_vacation[index].end_date = end_date

            this.form.accrued.common_vacation[index].quantity = this.roundNumber(moment(end_date, "YYYY-MM-DD").diff(moment(start_date, "YYYY-MM-DD"), 'days', true))

        },
        changePaymentCommonVacation(index){
            this.calculateTotal()
        },
        // vacaciones disfrutadas


        // vacaciones compensadas
        clickAddPaidVacation(){

            const salary_validation = this.salaryValidation()
            if(!salary_validation.success) return this.$message.warning(salary_validation.message)

            const quantity = 1
            const date_range = this.getStartEndDateRange(quantity)

            this.form.accrued.paid_vacation.push({
                start_date : date_range[0],
                end_date : date_range[1],
                quantity :  quantity,
                payment :  0,
                start_end_date: date_range
            })

        },
        clickCancelPaidVacation(index){
            this.form.accrued.paid_vacation.splice(index, 1)
            this.calculateTotal()
        },
        changePaidVacationStartEndDate(index) {
            const start_end_date = this.form.accrued.paid_vacation[index].start_end_date;
            const start_date = start_end_date[0];
            const end_date = start_end_date[1];
            const admisionDate = moment(this.form.period.admision_date, 'YYYY-MM-DD');

            if (admisionDate.isAfter(start_date)) {
                this.$message.warning('La fecha de inicio de vacaciones no puede ser anterior a la fecha de ingreso del empleado');
                this.form.accrued.paid_vacation[index].start_end_date = [admisionDate.format('YYYY-MM-DD'), end_date];
                return;
            }

            const quantity = this.getBusinessDays(start_date, end_date);

            const payment = this.roundNumber((this.form.accrued.total_base_salary / this.quantity_days_month) * quantity);

            this.form.accrued.paid_vacation[index].quantity = quantity;
            this.form.accrued.paid_vacation[index].payment = payment;
            this.calculateTotal();
        },
        // vacaciones compensadas

        // ayudas
        clickAddAid(){

            this.form.accrued.aid.push({
                salary_assistance :  undefined,
                non_salary_assistance :  undefined,
            })

        },
        clickCancelAid(index){
            this.form.accrued.aid.splice(index, 1)
            this.calculateTotal()
        },
        changeSalaryAid(index){
            this.calculateTotal()
        },
        // ayudas
        
        // otros conceptos
        clickAddOtherConcepts(){

            this.form.accrued.other_concepts.push({
                salary_concept :  undefined,
                non_salary_concept :  undefined,
                description_concept :  null,
            })

        },
        clickCancelOtherConcepts(index){
            this.form.accrued.other_concepts.splice(index, 1)
            this.calculateTotal()
        },
        changeSalaryOtherConcepts(index){
            this.calculateTotal()
        },
        // otros conceptos

        // comisiones
        clickAddCommission(){

            this.form.accrued.commissions.push({
                commission :  0,
            })

        },
        clickCancelCommission(index){
            this.form.accrued.commissions.splice(index, 1)
            this.calculateTotal()
        },
        changeCommission(index){
            this.calculateTotal()
        },
        // comisiones
        
        // Bono EPCTVs
        clickAddEpctvBonus(){

            this.form.accrued.epctv_bonuses.push({
                paymentS :  undefined,
                paymentNS :  undefined,
                salary_food_payment :  undefined,
                non_salary_food_payment :  undefined,
            })

        },
        clickCancelEpctvBonus(index){
            this.form.accrued.epctv_bonuses.splice(index, 1)
            this.calculateTotal()
        },
        changeEpctvBonus(index){
            this.calculateTotal()
        },
        // Bono EPCTVs

        // pagos a terceros
        clickAddThirdPartyPayment(){

            this.form.accrued.third_party_payments.push({
                third_party_payment :  0,
            })

        },
        clickCancelThirdPartyPayment(index){
            this.form.accrued.third_party_payments.splice(index, 1)
            this.calculateTotal()
        },
        changeThirdPartyPayment(index){
            this.calculateTotal()
        },
        // pagos a terceros
        
        // anticipos
        clickAddAdvance(){

            this.form.accrued.advances.push({
                advance :  0,
            })

        },
        clickCancelAdvance(index){
            this.form.accrued.advances.splice(index, 1)
            this.calculateTotal()
        },
        changeAdvance(index){
            this.calculateTotal()
        },
        // anticipos
        
        // compensaciones
        clickAddCompensation(){

            this.form.accrued.compensations.push({
                ordinary_compensation :  0,
                extraordinary_compensation :  0,
            })

        },
        clickCancelCompensation(index){
            this.form.accrued.compensations.splice(index, 1)
            this.calculateTotal()
        },
        changeCompensation(index){
            this.calculateTotal()
        },
        // compensaciones

        // huelgas
        clickCancelLegalStrike(index){
            this.form.accrued.legal_strike.splice(index, 1)
            this.calculateTotal()
        },
        // changeLegalStrikePayment(index){
        //     this.calculateTotal()
        // },
        changeLegalStrikeStartEndDate(index){

            const start_end_date = this.form.accrued.legal_strike[index].start_end_date
            const start_date = start_end_date[0]
            const end_date = start_end_date[1]
            const quantity = getDiffInDays(start_date, end_date)

            this.form.accrued.legal_strike[index].quantity = quantity
            this.form.accrued.legal_strike[index].start_date = start_date
            this.form.accrued.legal_strike[index].end_date = end_date

        },
        clickAddLegalStrike(){

            const quantity = 1
            const date_range = getArrayStartEndDate(quantity)

            this.form.accrued.legal_strike.push({
                start_date : date_range[0],
                end_date : date_range[1],
                quantity :  quantity,
                start_end_date: date_range,
                // payment :  0
            })

        },
        // huelgas
    }
}