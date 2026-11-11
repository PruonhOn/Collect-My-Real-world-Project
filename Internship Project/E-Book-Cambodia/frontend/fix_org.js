const fs = require("fs");
const p = "src/api/index.js";
let c = fs.readFileSync(p, "utf8");
c = c.replace("return api.post(/admin/organization/members/?_method=PUT, form)", "return api.post(`/admin/organization/members/${id}?_method=PUT`, form)");
c = c.replace("export const adminDeleteMember = (id) => api.delete(/admin/organization/members/)", "export const adminDeleteMember = (id) => api.delete(`/admin/organization/members/${id}`)");
c = c.replace("export const adminUpdatePartner = (id, data) => api.put(/admin/organization/partners/, data)", "export const adminUpdatePartner = (id, data) => api.put(`/admin/organization/partners/${id}`, data)");
c = c.replace("export const adminDeletePartner = (id) => api.delete(/admin/organization/partners/)", "export const adminDeletePartner = (id) => api.delete(`/admin/organization/partners/${id}`)");
fs.writeFileSync(p, c);
console.log("ok");
